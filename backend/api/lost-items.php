<?php
require_once __DIR__ . '/../config/Cors.php';
require_once __DIR__ . '/../controllers/LostItemController.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

Cors::enable();

$method = $_SERVER['REQUEST_METHOD'];
$controller = new LostItemController();

if ($method === 'GET') {
    $controller->getItems();
} else {
    // Protected routes
    $user = AuthMiddleware::authenticate();
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $controller->createItem($data, $user['id']);
    } else if ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        $controller->updateStatus($data, $user['id']);
    } else {
        Response::error("Method not allowed.", 405);
    }
}
?>
