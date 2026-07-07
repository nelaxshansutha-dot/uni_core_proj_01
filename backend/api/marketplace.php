<?php
require_once __DIR__ . '/../config/Cors.php';
Cors::enable();
require_once __DIR__ . '/../controllers/MarketplaceController.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';


$method = $_SERVER['REQUEST_METHOD'];
$controller = new MarketplaceController();

// All operations require authentication
$user = AuthMiddleware::authenticate();

if ($method === 'GET') {
    $controller->getItems();
} else if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $controller->createItem($data, $user['id']);
} else if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['status']) && !isset($data['item_name'])) {
        $controller->updateStatus($data, $user['id']);
    } else {
        $controller->updateListing($data, $user['id']);
    }
} else if ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $controller->deleteItem($data, $user['id']);
} else {
    Response::error("Method not allowed.", 405);
}

?>
