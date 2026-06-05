<?php
require 'backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'peer_learning_app_notification'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        $db->exec("ALTER TABLE users ADD COLUMN peer_learning_app_notification TINYINT(1) NOT NULL DEFAULT 1");
        echo "OK: Added peer_learning_app_notification column to users table.\n";
    } else {
        echo "SKIP: Column peer_learning_app_notification already exists.\n";
    }
    
    echo "Migration complete.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
