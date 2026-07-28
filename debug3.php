<?php
require 'backend/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/backend/');
$dotenv->load();
require 'backend/Config/Database.php';

$db = \Config\Database::getInstance()->getConnection();
$stmt = $db->query("SHOW COLUMNS FROM course_units");
$modules = $stmt->fetchAll(\PDO::FETCH_ASSOC);
print_r($modules);

$stmt2 = $db->query("SELECT * FROM course_units");
print_r($stmt2->fetchAll(\PDO::FETCH_ASSOC));
