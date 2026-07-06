<?php
require_once __DIR__ . '/../config/Cors.php';
Cors::enable();
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';


$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($method === 'GET' && $action === 'recent-activity') {
    $user = AuthMiddleware::authenticate();
    $controller = new DashboardController();
    $controller->getRecentActivity($user);
} else {
    Response::error("Method not allowed or invalid action.", 405);
}
?>
