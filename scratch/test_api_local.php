<?php
$_SERVER['REQUEST_URI'] = '/uni_core_proj_01/backend/api/notes?forRep=true';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['forRep'] = 'true';

require __DIR__ . '/../backend/vendor/autoload.php';
use Firebase\JWT\JWT;
use Middleware\AuthMiddleware;

$payload = [
    'userID' => 57,
    'role'   => 'course_representative',
    'enrollmentNo' => 'rep_uwu/cst/23/088', // WAIT, the payload has enrollmentNo!
    'jti'    => uniqid('jwt_', true),
    'iat'    => time(),
    'exp'    => time() + 3600 * 24
];
$token = JWT::encode($payload, AuthMiddleware::getSecretKey(), 'HS256');

$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

require __DIR__ . '/../backend/api/index.php';
