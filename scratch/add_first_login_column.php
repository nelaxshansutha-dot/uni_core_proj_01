<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    $db->exec("ALTER TABLE Course_representative ADD COLUMN is_first_login TINYINT(1) DEFAULT 1 AFTER hash_password;");
    echo "Column is_first_login successfully added to Course_representative.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
