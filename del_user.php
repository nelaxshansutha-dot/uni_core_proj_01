<?php
require 'backend/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/backend/');
$dotenv->load();
require 'backend/Config/Database.php';
$db = \Config\Database::getInstance()->getConnection();
$db->exec("DELETE FROM users WHERE email='jeevaa200320@gmail.com'");
echo "Deleted user!";
