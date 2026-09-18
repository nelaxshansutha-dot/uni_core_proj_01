<?php
require __DIR__ . '/../backend/vendor/autoload.php';

$identifier = 'rep_uwu/cst/23/088';
$role = 'course_representative';

// I'll update the rep hash to a known password
$db = new PDO('mysql:host=localhost;dbname=uni_core_proj_01', 'root', '');
$knownPassword = 'reppassword123';
$hash = password_hash($knownPassword, PASSWORD_DEFAULT);
$db->exec("UPDATE course_representative SET hash_password = '$hash' WHERE rep_id_string = '$identifier'");

$user = \Models\User::loadByIdentifier($identifier, $role);
if ($user) {
    if ($user->login($knownPassword)) {
        echo "Rep Login Success!\n";
    } else {
        echo "Rep Login Failed!\n";
    }
} else {
    echo "Rep not found\n";
}
