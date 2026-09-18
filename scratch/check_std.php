<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$db = new PDO('mysql:host=localhost;dbname=uni_core_proj_01', 'root', '');
$stmt = $db->query("SELECT enrollmentNo, std_year FROM student LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
