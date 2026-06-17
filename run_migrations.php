<?php
require 'backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    
    // Add lost_item_sms_notification
    $stmt = $db->query("SHOW COLUMNS FROM Users LIKE 'lost_item_sms_notification'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE Users ADD COLUMN lost_item_sms_notification TINYINT(1) NOT NULL DEFAULT 0");
        echo "OK: Added lost_item_sms_notification column.\n";
    } else {
        echo "SKIP: Column lost_item_sms_notification already exists.\n";
    }

    // Add has_seen_lost_item_popup
    $stmt = $db->query("SHOW COLUMNS FROM Users LIKE 'has_seen_lost_item_popup'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE Users ADD COLUMN has_seen_lost_item_popup TINYINT(1) NOT NULL DEFAULT 0");
        echo "OK: Added has_seen_lost_item_popup column.\n";
    } else {
        echo "SKIP: Column has_seen_lost_item_popup already exists.\n";
    }

    // Add peer_learning_app_notification just in case
    $stmt = $db->query("SHOW COLUMNS FROM Users LIKE 'peer_learning_app_notification'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE Users ADD COLUMN peer_learning_app_notification TINYINT(1) NOT NULL DEFAULT 1");
        echo "OK: Added peer_learning_app_notification column.\n";
    } else {
        echo "SKIP: Column peer_learning_app_notification already exists.\n";
    }
    
    echo "Migration complete.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
