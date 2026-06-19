<?php
require_once __DIR__ . '/../backend/config/Database.php';
$db = (new Database())->getConnection();

// Let's find the rep user
$stmt = $db->query("SELECT u.userID, u.email, u.role, cr.enrollmentNo, cr.hash_password, cr.is_first_login FROM Users u JOIN Course_representative cr ON u.userID = cr.userID");
$reps = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Reps in database:\n";
print_r($reps);
?>
