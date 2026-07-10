<?php
require_once __DIR__ . '/backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    
    // Check if the columns exist before dropping them to avoid errors if run multiple times
    $stmt = $db->query("SHOW COLUMNS FROM lost_items LIKE 'last_seen_date'");
    if ($stmt->rowCount() > 0) {
        $db->exec("ALTER TABLE lost_items DROP COLUMN last_seen_date");
        echo "Dropped last_seen_date\n";
    } else {
        echo "last_seen_date already dropped\n";
    }

    $stmt2 = $db->query("SHOW COLUMNS FROM lost_items LIKE 'last_seen_time'");
    if ($stmt2->rowCount() > 0) {
        $db->exec("ALTER TABLE lost_items DROP COLUMN last_seen_time");
        echo "Dropped last_seen_time\n";
    } else {
        echo "last_seen_time already dropped\n";
    }
    
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
