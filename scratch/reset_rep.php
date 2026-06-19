<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    
    $hashed = password_hash('UniCore2026!', PASSWORD_BCRYPT);
    $db->exec("UPDATE Course_representative SET hash_password = '{$hashed}' WHERE userID = 9");
    
    echo "Password reset successfully for Rep (userID=9).\n";

} catch (Exception $e) {
    echo $e->getMessage();
}
?>
