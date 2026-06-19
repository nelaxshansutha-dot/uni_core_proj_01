<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    
    $db->exec("UPDATE Course_representative SET rep_id_string = 'REP_UWU/CST/23/088' WHERE userID = 9");
    echo "Updated rep_id_string for userID=9.\n";

} catch (Exception $e) {
    echo $e->getMessage();
}
?>
