<?php
require_once __DIR__ . '/../config/Cors.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

Cors::enable();

$method = $_SERVER['REQUEST_METHOD'];
$controller = new AdminController();

$user = AuthMiddleware::authenticate();
AuthMiddleware::requireRole($user, ['admin']);

if ($method === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    if ($action === 'search-students') {
        $q = isset($_GET['q']) ? $_GET['q'] : '';
        $controller->searchStudents($q);
    } else {
        Response::error("Invalid action.", 404);
    }
} else if ($method === 'POST') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    if ($action === 'assign-rep') {
        $data = json_decode(file_get_contents("php://input"), true);
        $controller->assignRep($data);
    } else {
        Response::error("Invalid action.", 404);
    }
} else {
    Response::error("Method not allowed.", 405);
}
?>
