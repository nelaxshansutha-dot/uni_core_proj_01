<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // First let's check what columns exist
    $stmt = $conn->query("SHOW COLUMNS FROM marketplace");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $alterations = [];

    // Rename columns if they still have the old names
    if (in_array('productID', $columns) && !in_array('id', $columns)) {
        $alterations[] = "ALTER TABLE marketplace CHANGE productID id INT AUTO_INCREMENT";
    }
    if (in_array('userID', $columns) && !in_array('seller_id', $columns)) {
        try {
            $conn->exec("ALTER TABLE marketplace DROP FOREIGN KEY marketplace_ibfk_1");
        } catch(Exception $e) {}
        $alterations[] = "ALTER TABLE marketplace CHANGE userID seller_id INT NOT NULL";
        $alterations[] = "ALTER TABLE marketplace ADD CONSTRAINT fk_seller FOREIGN KEY (seller_id) REFERENCES Users(userID) ON DELETE CASCADE";
    }
    if (in_array('product_name', $columns) && !in_array('item_name', $columns)) {
        $alterations[] = "ALTER TABLE marketplace CHANGE product_name item_name VARCHAR(100) NOT NULL";
    }
    if (in_array('product_image', $columns) && !in_array('image_url', $columns)) {
        $alterations[] = "ALTER TABLE marketplace CHANGE product_image image_url VARCHAR(255)";
    }
    if (in_array('contact_no', $columns) && !in_array('phone_number', $columns)) {
        $alterations[] = "ALTER TABLE marketplace CHANGE contact_no phone_number VARCHAR(30)";
    }

    // Add missing columns
    if (!in_array('description', $columns)) {
        $alterations[] = "ALTER TABLE marketplace ADD COLUMN description TEXT NULL";
    }
    if (!in_array('condition_type', $columns)) {
        $alterations[] = "ALTER TABLE marketplace ADD COLUMN condition_type VARCHAR(20) NOT NULL DEFAULT 'new'";
    }
    if (!in_array('usage_duration', $columns)) {
        $alterations[] = "ALTER TABLE marketplace ADD COLUMN usage_duration VARCHAR(100) NULL";
    }
    if (!in_array('image_url2', $columns)) {
        $alterations[] = "ALTER TABLE marketplace ADD COLUMN image_url2 VARCHAR(255) NULL";
    }
    if (!in_array('image_url3', $columns)) {
        $alterations[] = "ALTER TABLE marketplace ADD COLUMN image_url3 VARCHAR(255) NULL";
    }
    if (!in_array('image_url4', $columns)) {
        $alterations[] = "ALTER TABLE marketplace ADD COLUMN image_url4 VARCHAR(255) NULL";
    }
    if (!in_array('status', $columns)) {
        $alterations[] = "ALTER TABLE marketplace ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'available'";
    }

    foreach ($alterations as $sql) {
        try {
            $conn->exec($sql);
            echo "OK: $sql\n";
        } catch (Exception $e) {
            echo "FAILED: $sql \n  -> " . $e->getMessage() . "\n";
        }
    }
    echo "Schema fix complete.\n";
    
} catch(Exception $e) {
    echo "Connection Error: " . $e->getMessage();
}
?>
