<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/SMSService.php';

$pendingFile = __DIR__ . '/../data/pending_sms_lost_items.txt';

if (!file_exists($pendingFile)) {
    echo "No pending SMS items.\n";
    exit;
}

// Read the file and get unique lost IDs
$content = file_get_contents($pendingFile);
// Empty the file immediately to avoid race conditions with new submissions
file_put_contents($pendingFile, '', LOCK_EX);

$lostIDs = array_filter(array_unique(array_map('trim', explode(PHP_EOL, $content))));

if (empty($lostIDs)) {
    echo "No valid lost IDs found.\n";
    exit;
}

try {
    $db = \Config\Database::getInstance()->getConnection();
    
    // Fetch opted-in phone numbers
    $stmtPhones = $db->query("SELECT phoneNum FROM users WHERE phoneNum IS NOT NULL AND phoneNum != '' AND lost_item_sms_notification = 1");
    $phones = $stmtPhones->fetchAll(PDO::FETCH_COLUMN);
    $phones = array_unique($phones);

    if (empty($phones)) {
        echo "No opted-in users with valid phone numbers found.\n";
        exit;
    }

    foreach ($lostIDs as $lostID) {
        if (!is_numeric($lostID)) continue;

        // Fetch lost item details
        $itemStmt = $db->prepare("SELECT lostItemName, description, last_seen_place, last_seen_datetime, contact_number FROM lost_items WHERE lostID = :lid");
        $itemStmt->execute([':lid' => $lostID]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            $itemName = $item['lostItemName'] ?? 'An item';
            $descShort = substr($item['description'] ?? '', 0, 50) . (strlen($item['description'] ?? '') > 50 ? '...' : '');
            $place = $item['last_seen_place'] ?? 'Unknown';
            
            // Format datetime if valid
            $time = $item['last_seen_datetime'] ?? 'Unknown';
            if ($time !== 'Unknown' && strtotime($time)) {
                $time = date('Y-m-d H:i', strtotime($time));
            }
            
            $contact = $item['contact_number'] ?? 'Unknown';
            
            $smsMessage = "UniCore Lost Item\nItem: $itemName\nDesc: $descShort\nSeen: $place at $time\nCall: $contact";

            // Send bulk SMS
            \Utils\SMSService::sendSMS($phones, $smsMessage);
            echo "Sent bulk SMS for lostID $lostID to " . count($phones) . " recipients.\n";
        }
    }

} catch (\Exception $e) {
    echo "Error processing SMS queue: " . $e->getMessage() . "\n";
}
