<?php
$content = file_get_contents('backend/controllers/AuthController.php');

$content = str_replace("'student'", 'User::ROLE_STUDENT', $content);
$content = str_replace("'staff'", 'User::ROLE_STAFF', $content);
$content = str_replace("'rep'", 'User::ROLE_REP', $content);
$content = str_replace("'admin'", 'User::ROLE_ADMIN', $content);
$content = str_replace('"student"', 'User::ROLE_STUDENT', $content);
$content = str_replace('"staff"', 'User::ROLE_STAFF', $content);
$content = str_replace('"rep"', 'User::ROLE_REP', $content);
$content = str_replace('"admin"', 'User::ROLE_ADMIN', $content);

file_put_contents('backend/controllers/AuthController.php', $content);
echo "Replaced all roles in AuthController.php\n";
