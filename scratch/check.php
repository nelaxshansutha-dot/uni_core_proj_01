<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$db = new PDO('mysql:host=localhost;dbname=uni_core_proj_01', 'root', '');
$stmt = $db->prepare("SELECT * FROM users WHERE email='cst23088@std.uwu.ac.lk'");
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));
