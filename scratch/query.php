<?php
require __DIR__ . '/../backend/vendor/autoload.php';

$db = new PDO('mysql:host=localhost;dbname=uni_core_proj_01', 'root', '');
$stmt = $db->prepare('SELECT * FROM course_representative WHERE userID = 57');
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));
