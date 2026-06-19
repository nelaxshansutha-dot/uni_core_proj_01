<?php
require_once __DIR__ . '/../backend/config/Database.php';
$db = (new Database())->getConnection();

// Keep only the latest entry for each user in Course_representative
$db->exec("
    DELETE c1 FROM Course_representative c1
    INNER JOIN Course_representative c2 
    WHERE c1.userID = c2.userID AND c1.repID < c2.repID
");
echo "Cleaned up duplicates.\n";

// Add UNIQUE constraint
try {
    $db->exec("ALTER TABLE Course_representative ADD UNIQUE (userID)");
    echo "Added UNIQUE constraint on userID.\n";
} catch (PDOException $e) {
    echo "Constraint may already exist or error: " . $e->getMessage() . "\n";
}
?>
