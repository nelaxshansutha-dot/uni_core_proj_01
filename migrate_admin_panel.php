<?php
require_once __DIR__ . '/backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    echo "Starting database migrations for Admin Panel...\n";

    $queries = [
        // Add is_active to users
        "ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE",

        // Add status and flag to lost_items
        "ALTER TABLE lost_items MODIFY COLUMN status ENUM('lost', 'found', 'hidden', 'removed') DEFAULT 'lost'",
        "ALTER TABLE lost_items ADD COLUMN is_flagged BOOLEAN DEFAULT FALSE",

        // Add status and flag to marketplace
        "ALTER TABLE marketplace MODIFY COLUMN status ENUM('available', 'sold', 'hidden', 'removed') DEFAULT 'available'",
        "ALTER TABLE marketplace ADD COLUMN is_flagged BOOLEAN DEFAULT FALSE",

        // Add status and flag to notes
        "ALTER TABLE notes ADD COLUMN status ENUM('active', 'hidden', 'removed') DEFAULT 'active'",
        "ALTER TABLE notes ADD COLUMN is_flagged BOOLEAN DEFAULT FALSE",

        // Create reports table
        "CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reporter_id INT NOT NULL,
            content_type ENUM('lost_item', 'marketplace', 'notes') NOT NULL,
            content_id INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            status ENUM('pending', 'resolved', 'ignored') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        // Create admin_logs table
        "CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(255) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_id INT NOT NULL,
            details TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
        )"
    ];

    foreach ($queries as $sql) {
        try {
            $db->exec($sql);
            echo "SUCCESS: " . substr($sql, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "SKIP/ERROR: " . $e->getMessage() . "\n";
        }
    }

    echo "Migration completed.\n";
} catch (Exception $e) {
    echo "Fatal connection error: " . $e->getMessage() . "\n";
}
?>
