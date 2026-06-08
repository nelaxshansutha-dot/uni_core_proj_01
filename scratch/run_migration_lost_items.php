<?php
require_once __DIR__ . '/../backend/config/Database.php';
$db = (new Database())->getConnection();
try {
    $db->exec("ALTER TABLE lost_items ADD COLUMN status ENUM('lost', 'found', 'hidden', 'removed') DEFAULT 'lost'");
    echo "SUCCESS\n";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
