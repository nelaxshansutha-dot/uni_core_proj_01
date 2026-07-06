<?php
require_once __DIR__ . '/../config/Cors.php';
Cors::enable();
require_once __DIR__ . '/../controllers/ProfileController.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';


$method = $_SERVER['REQUEST_METHOD'];
$user = AuthMiddleware::authenticate();
$controller = new ProfileController();

if ($method === 'GET') {
    $controller->getProfile($user['id']);
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $controller->updateProfile($data, $user['id']);
} else {
    Response::error("Method not allowed", 405);
}
?>
