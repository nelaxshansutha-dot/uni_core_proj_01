<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // 1. Get current columns
    $stmt = $conn->query("SHOW COLUMNS FROM marketplace");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Current columns: " . implode(', ', $columns) . "\n";
    
    // 2. Drop foreign key constraint first
    // Try dropping fk_seller first
    try {
        $conn->exec("ALTER TABLE marketplace DROP FOREIGN KEY fk_seller");
        echo "Dropped fk_seller foreign key.\n";
    } catch (Exception $e) {
        echo "Note: Could not drop fk_seller (might not exist): " . $e->getMessage() . "\n";
    }

    // Try dropping marketplace_ibfk_1
    try {
        $conn->exec("ALTER TABLE marketplace DROP FOREIGN KEY marketplace_ibfk_1");
        echo "Dropped marketplace_ibfk_1 foreign key.\n";
    } catch (Exception $e) {
        echo "Note: Could not drop marketplace_ibfk_1 (might not exist): " . $e->getMessage() . "\n";
    }

    // 3. Rename Primary Key to productID
    if (in_array('id', $columns)) {
        $conn->exec("ALTER TABLE marketplace CHANGE id productID INT AUTO_INCREMENT");
        echo "Renamed id to productID.\n";
    } elseif (in_array('sellerID', $columns) && !in_array('productID', $columns)) {
        // In case they had sellerID as the primary key
        $conn->exec("ALTER TABLE marketplace CHANGE sellerID productID INT AUTO_INCREMENT");
        echo "Renamed sellerID to productID.\n";
    } else {
        echo "No PK rename needed or already done.\n";
    }

    // 4. Rename Foreign Key to userID
    // Refresh column list
    $stmt = $conn->query("SHOW COLUMNS FROM marketplace");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('seller_id', $columns)) {
        $conn->exec("ALTER TABLE marketplace CHANGE seller_id userID INT NOT NULL");
        echo "Renamed seller_id to userID.\n";
    } elseif (in_array('sellerID', $columns)) {
        $conn->exec("ALTER TABLE marketplace CHANGE sellerID userID INT NOT NULL");
        echo "Renamed sellerID to userID.\n";
    } else {
        echo "No FK rename needed or already done.\n";
    }

    // 5. Re-add foreign key constraint pointing userID to Users(userID)
    try {
        $conn->exec("ALTER TABLE marketplace ADD CONSTRAINT fk_marketplace_user FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE");
        echo "Added fk_marketplace_user foreign key constraint successfully.\n";
    } catch (Exception $e) {
        echo "Error adding foreign key: " . $e->getMessage() . "\n";
    }

    echo "Schema update completed.\n";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
