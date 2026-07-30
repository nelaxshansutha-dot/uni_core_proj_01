<?php
require 'backend/vendor/autoload.php';
require 'backend/config/Database.php';
require 'backend/middleware/AuthMiddleware.php';
$payload = [
    'userID' => 1,
    'role' => 'student',
    'enrollmentNo' => 'UWU/CST/23/088',
    'jti' => uniqid('jwt_', true),
    'iat' => time(),
    'exp' => time() + 3600
];
echo \Firebase\JWT\JWT::encode($payload, \Middleware\AuthMiddleware::getSecretKey(), 'HS256');
