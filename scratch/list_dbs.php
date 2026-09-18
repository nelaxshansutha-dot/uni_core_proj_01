<?php
$db = new PDO('mysql:host=localhost', 'root', '');
$stmt = $db->query("SHOW DATABASES");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
