<?php
require_once __DIR__ . '/../config/Cors.php';
require_once __DIR__ . '/../controllers/MarketplaceController.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

Cors::enable();

$method = $_SERVER['REQUEST_METHOD'];
$controller = new MarketplaceController();

// if ($method === 'GET') {
//     $controller->getItems();
// } else {
    // All write operations require authentication
    $user = AuthMiddleware::authenticate();

    $data = json_decode(file_get_contents("php://input"), true);
    if ($method === 'GET') {
    $controller->getItems($user['id']); // optional: user-based filtering
}

    else if ($method === 'POST') {//POST ITAEM
        $controller->createItem($data, $user['id']);
    } else if ($method === 'PUT') {//update 
        if (isset($data['status']) && !isset($data['item_name'])) {
            $controller->updateStatus($data, $user['id']);
        } else {
            $controller->updateListing($data, $user['id']);
        }
    } else if ($method === 'DELETE') {//delete
        $controller->deleteItem($data, $user['id']);
    } else {
        Response::error("Method not allowed.", 405);
    }

?>
