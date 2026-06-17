<?php
require 'backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    
    // Add missing columns
    $stmt = $db->query("SHOW COLUMNS FROM Lost_items LIKE 'description'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE Lost_items ADD COLUMN description TEXT AFTER item_name");
        echo "Added description.\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM Lost_items LIKE 'last_seen_place'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE Lost_items ADD COLUMN last_seen_place VARCHAR(255) AFTER description");
        echo "Added last_seen_place.\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM Lost_items LIKE 'last_seen_datetime'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE Lost_items ADD COLUMN last_seen_datetime DATETIME AFTER last_seen_place");
        echo "Added last_seen_datetime.\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM Lost_items LIKE 'contact_number'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE Lost_items CHANGE contact_no contact_number VARCHAR(20)");
        echo "Renamed contact_no to contact_number.\n";
    }
    
    echo "Lost_items migration complete.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
