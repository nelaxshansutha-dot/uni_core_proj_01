<?php
require_once __DIR__ . '/backend/config/Database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("SELECT userID, email, is_verified FROM Users ORDER BY userID DESC LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
