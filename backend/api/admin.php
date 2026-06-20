<?php
require_once __DIR__ . '/../config/Cors.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';
//pass123
Cors::enable();

$method = $_SERVER['REQUEST_METHOD'];
$controller = new AdminController();




$user = AuthMiddleware::authenticate();
AuthMiddleware::requireRole($user, ['admin']);

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($method === 'GET') {
    if ($action === 'dashboard') {
        $controller->getDashboardStats();
    } else if ($action === 'users') {
        $q = isset($_GET['q']) ? $_GET['q'] : '';
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $controller->getUsers($q, $role);
    } else if ($action === 'search-students') {
        $q = isset($_GET['q']) ? $_GET['q'] : '';
        $controller->searchStudents($q);
    } else if ($action === 'content') {
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        $controller->getContent($type);
    } else if ($action === 'reports') {
        $controller->getReports();
    } else {
        Response::error("Invalid action.", 404);
    }
} else if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if ($action === 'users') {
        $controller->createUser($data, $user['id']);
    } else if ($action === 'assign-rep') {
        $controller->assignRep($data, $user['id']);
    } else {
        Response::error("Invalid action.", 404);
    }
} else if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($action === 'users') {
        $controller->updateUser($id, $data, $user['id']);
    } else {
        Response::error("Invalid action.", 404);
    }
} else if ($method === 'PATCH') {
    $data = json_decode(file_get_contents("php://input"), true);
    if ($action === 'users-status') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $controller->toggleUserStatus($id, $data, $user['id']);
    } else if ($action === 'content-status') {
        $controller->updateContentStatus($data, $user['id']);
    } else if ($action === 'reports-status') {
        $controller->updateReportStatus($data, $user['id']);
    } else {
        Response::error("Invalid action.", 404);
    }
} else {
    Response::error("Method not allowed.", 405);
}
?>
