<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SHOW COLUMNS FROM marketplace");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in marketplace:\n";
    print_r($columns);
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
