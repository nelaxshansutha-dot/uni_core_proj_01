<?php
require 'backend/config/Database.php';
$db = (new Database())->getConnection();

$alterations = [
    "ALTER TABLE marketplace ADD COLUMN condition_type VARCHAR(10) NOT NULL DEFAULT 'new'",
    "ALTER TABLE marketplace ADD COLUMN location VARCHAR(255) NULL",
    "ALTER TABLE marketplace ADD COLUMN phone_number VARCHAR(30) NULL",
    "ALTER TABLE marketplace ADD COLUMN usage_duration VARCHAR(100) NULL",
    "ALTER TABLE marketplace ADD COLUMN image_url2 VARCHAR(255) NULL",
    "ALTER TABLE marketplace ADD COLUMN image_url3 VARCHAR(255) NULL",
    "ALTER TABLE marketplace ADD COLUMN image_url4 VARCHAR(255) NULL",
];

foreach ($alterations as $sql) {
    try {
        $db->exec($sql);
        echo "OK: $sql\n";
    } catch (Exception $e) {
        echo "SKIP: " . $e->getMessage() . "\n";
    }
}
echo "Migration complete.\n";
?>
