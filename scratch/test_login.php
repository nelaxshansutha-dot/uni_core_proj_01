<?php
require_once __DIR__ . '/../backend/controllers/AuthController.php';
$c = new AuthController();
$c->login(['enrollment_no' => 'ADMIN001', 'password' => 'password']);
?>
