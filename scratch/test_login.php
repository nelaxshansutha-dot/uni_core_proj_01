<?php
require __DIR__ . '/../backend/vendor/autoload.php';

$identifier = 'UWU/CST/23/088';
$role = 'student';
$password = 'studentpassword'; // I need to know what the real password is to test it, but I don't. I can hash a known password and set it.

$db = new PDO('mysql:host=localhost;dbname=uni_core_proj_01', 'root', '');
$knownPassword = 'password123';
$hash = password_hash($knownPassword, PASSWORD_DEFAULT);
$db->exec("UPDATE users SET hash_password = '$hash' WHERE userID = 57");

$user = \Models\User::loadByIdentifier($identifier, $role);
if ($user) {
    if ($user->login($knownPassword)) {
        echo "Login Success!\n";
    } else {
        echo "Login Failed!\n";
    }
} else {
    echo "User not found\n";
}
