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

    $user = AuthMiddleware::authenticate();

    if ($method === 'POST') {

        $data = $_POST;
        $file = $_FILES['item_image'] ?? null;

        $controller->createItem($data, $file, $user['id']);

    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (isset($data['update_preference'])) {
            require_once __DIR__ . '/../models/User.php';
            $userModel = new User();
            $smsPref = isset($data['lost_item_sms_notification']) ? (int)$data['lost_item_sms_notification'] : 0;
            $popupSeen = isset($data['has_seen_lost_item_popup']) ? (int)$data['has_seen_lost_item_popup'] : 1;
            
            if ($userModel->updateNotificationSettings($user['id'], $smsPref, $popupSeen)) {
                Response::success("Preferences updated.");
            } else {
                Response::error("Failed to update preferences.", 500);
            }
        } else {
            Response::error("Invalid action", 400);
        }
    } elseif ($method === 'DELETE') {
        $itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($itemId <= 0) {
            Response::error("Missing item ID.", 400);
        }
        $controller->deleteItem($itemId, $user['id']);
    } else {

        Response::error("Method not allowed", 405);
    }
}
?>