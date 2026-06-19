<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    $db->exec("ALTER TABLE Users DROP COLUMN enrollment_no, DROP COLUMN staff_id, DROP COLUMN rep_id;");
    echo "Columns successfully removed from the database.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'check that column/key exists') !== false) {
        echo "Columns already removed or do not exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
