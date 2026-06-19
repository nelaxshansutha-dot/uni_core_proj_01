<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    
    $stmt = $db->query("SELECT u.userID, u.role, cr.hash_password as rep_hash, cr.is_first_login FROM Users u JOIN Course_representative cr ON u.userID = cr.userID");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Reps Data:\n";
    print_r($data);

} catch (Exception $e) {
    echo $e->getMessage();
}
?>
