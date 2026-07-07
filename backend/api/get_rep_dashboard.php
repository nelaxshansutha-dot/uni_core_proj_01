<?php
require_once __DIR__ . '/../config/Cors.php';
Cors::enable();
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/PeerLearningController.php';
require_once __DIR__ . '/../utils/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Method not allowed", 405);
}

try {
    $user = AuthMiddleware::authenticate();
    
    if ($user['role'] !== 'rep') {
        Response::error("Forbidden: Only Course Representatives can access this endpoint.", 403);
    }

    $controller = new PeerLearningController();
    $controller->getRepDashboardData($user['id']);

} catch (Exception $e) {
    Response::error("Server error: " . $e->getMessage(), 500);
}
?>
