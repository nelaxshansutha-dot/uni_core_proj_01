<?php
require_once 'config/Database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Check if dummy rep exists
    $stmt = $db->prepare("SELECT userID FROM Users WHERE email = 'rep@unicore.com'");
    $stmt->execute();
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        $db->beginTransaction();
        // Insert User
        $stmt = $db->prepare("INSERT INTO Users (fname, lname, email, hash_password, role, is_verified) VALUES ('Test', 'Rep', 'rep@unicore.com', 'password', 'rep', 1)");
        $stmt->execute();
        $userId = $db->lastInsertId();

        // Insert Student
        $enrollment = 'UWU/CST/23/999';
        $stmt = $db->prepare("INSERT INTO Student (enrollmentNo, userID, courseID, std_year) VALUES (?, ?, 7, 1)");
        $stmt->execute([$enrollment, $userId]);

        // Insert Rep
        $stmt = $db->prepare("INSERT INTO Course_representative (userID, enrollmentNo, courseID) VALUES (?, ?, 7)");
        $stmt->execute([$userId, $enrollment]);
        $db->commit();

        echo "Created Dummy Rep successfully.\n";
    } else {
        echo "Dummy rep already exists.\n";
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
