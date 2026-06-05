<?php
require_once __DIR__ . '/backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    echo "=== USERS ===\n";
    $stmt = $db->query("SELECT id, enrollment_no, email, role, phone_number, lost_item_sms_notification FROM users");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\n=== STUDENTS ===\n";
    $stmt2 = $db->query("SELECT * FROM students");
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

    echo "\n=== PEER LEARNING REQUESTS ===\n";
    $stmt3 = $db->query("SELECT * FROM peer_learning_requests");
    print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
