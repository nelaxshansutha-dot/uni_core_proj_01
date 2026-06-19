<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    
    // Dump user 5's data from Users and Course_representative
    $stmt = $db->query("SELECT u.userID, u.role, u.hash_password as user_hash, cr.hash_password as rep_hash, cr.is_first_login FROM Users u LEFT JOIN Course_representative cr ON u.userID = cr.userID WHERE u.userID = 5");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "User Data:\n";
    print_r($data);

} catch (Exception $e) {
    echo $e->getMessage();
}
?>
