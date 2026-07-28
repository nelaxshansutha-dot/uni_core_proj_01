<?php
require 'backend/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/backend/');
$dotenv->load();
require 'backend/Config/Database.php';

$db = \Config\Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM course_units LIMIT 1");
$modules = $stmt->fetchAll(\PDO::FETCH_ASSOC);
print_r($modules);
