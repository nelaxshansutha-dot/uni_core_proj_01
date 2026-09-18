<?php
require __DIR__ . '/../backend/vendor/autoload.php';
use Firebase\JWT\JWT;
use Middleware\AuthMiddleware;

$payload = [
    'userID' => 57,
    'role'   => 'course_representative',
    'jti'    => uniqid('jwt_', true),
    'iat'    => time(),
    'exp'    => time() + 3600 * 24
];
$token = JWT::encode($payload, AuthMiddleware::getSecretKey(), 'HS256');

$ch = curl_init('http://localhost/uni_core_proj_01/backend/api/notes?forRep=true');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
echo "Response:\n$response\n";
