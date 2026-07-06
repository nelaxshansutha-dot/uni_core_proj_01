<?php
require_once __DIR__ . '/../config/Cors.php';
Cors::enable();
require_once __DIR__ . '/../controllers/NotificationController.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';


$method = $_SERVER['REQUEST_METHOD'];
$controller = new NotificationController();

$user = AuthMiddleware::authenticate();

if ($method === 'GET') {
    $controller->getUserNotifications($user['id']);
} else if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $controller->markAsRead($data, $user['id']);
} else {
    Response::error("Method not allowed.", 405);
}
?>
