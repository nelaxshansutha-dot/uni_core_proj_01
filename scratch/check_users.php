<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    
    $stmt = $db->query("SELECT userID, email, role, is_verified FROM Users");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Users Data:\n";
    print_r($data);

} catch (Exception $e) {
    echo $e->getMessage();
}
?>
