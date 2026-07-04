<?php
require_once __DIR__ . '/../config/Cors.php';
require_once __DIR__ . '/../controllers/PeerLearningController.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

Cors::enable();

$method = $_SERVER['REQUEST_METHOD'];
$controller = new PeerLearningController();

$user = AuthMiddleware::authenticate();

if ($method === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    if ($action === 'my-requests' && in_array($user['role'], ['student', 'rep'])) {
        $controller->getStudentRequests($user['id']);
    } else if ($action === 'course-requests' && $user['role'] === 'rep') {
        if (!isset($_GET['courseUnitID'])) Response::error("Missing courseUnitID");
        $controller->getCourseRequests($_GET['courseUnitID']);
    } else {
        Response::error("Invalid action or permissions.", 403);
    }
} else if ($method === 'POST') {
    AuthMiddleware::requireRole($user, ['student', 'rep']);
    $data = json_decode(file_get_contents("php://input"), true);
    $controller->createRequest($data, $user['id']);
} else if ($method === 'PUT') {
    AuthMiddleware::requireRole($user, ['rep']);
    $data = json_decode(file_get_contents("php://input"), true);
    $controller->updateStatus($data, $user['id']);
} else {
    Response::error("Method not allowed.", 405);
}
?>
