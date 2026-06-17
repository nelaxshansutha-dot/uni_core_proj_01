<?php
require 'backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    
    $stmt = $db->query("SHOW COLUMNS FROM Lost_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in Lost_items:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
