<?php
$db = new PDO('mysql:host=localhost;dbname=uni_core_proj_01', 'root', '');
$stmt = $db->query("SHOW TRIGGERS");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
