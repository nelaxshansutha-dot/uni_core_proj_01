<?php
$host = '127.0.0.1';
$db   = 'unicore_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add columns if they do not exist
    try {
        $pdo->exec("ALTER TABLE lost_items ADD COLUMN last_seen_date DATETIME NULL AFTER image_url");
        $pdo->exec("ALTER TABLE lost_items ADD COLUMN last_seen_place VARCHAR(255) NULL AFTER last_seen_date");
        $pdo->exec("ALTER TABLE lost_items ADD COLUMN contact_number VARCHAR(50) NULL AFTER last_seen_place");
        echo "Added columns to lost_items\n";
    } catch(Exception $e) { echo "lost_items: " . $e->getMessage() . "\n"; }

    try {
        $pdo->exec("ALTER TABLE marketplace ADD COLUMN brand VARCHAR(100) NULL AFTER item_name");
        $pdo->exec("ALTER TABLE marketplace ADD COLUMN location VARCHAR(255) NULL AFTER price");
        $pdo->exec("ALTER TABLE marketplace ADD COLUMN contact_number VARCHAR(50) NULL AFTER location");
        echo "Added columns to marketplace\n";
    } catch(Exception $e) { echo "marketplace: " . $e->getMessage() . "\n"; }

    try {
        $pdo->exec("ALTER TABLE notes ADD COLUMN course_unit VARCHAR(50) NULL AFTER course_code");
        echo "Added course_unit to notes\n";
    } catch(Exception $e) { echo "notes: " . $e->getMessage() . "\n"; }

    echo "Migration completed.\n";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
