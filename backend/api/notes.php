<?php
require_once __DIR__ . '/../config/Cors.php';
require_once __DIR__ . '/../controllers/NotesController.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

Cors::enable();

$method = $_SERVER['REQUEST_METHOD'];
$controller = new NotesController();

$user = AuthMiddleware::authenticate();

if ($method === 'GET') {
    $filters = [];
    if (isset($_GET['course_code'])) $filters['course_code'] = $_GET['course_code'];
    if (isset($_GET['year'])) $filters['year'] = $_GET['year'];
    if (isset($_GET['semester'])) $filters['semester'] = $_GET['semester'];
    
    $controller->getNotes($filters);
} else if ($method === 'POST') {
    AuthMiddleware::requireRole($user, ['staff', 'rep', 'admin']);
    
    // Notes are sent as multipart/form-data
    $data = $_POST; 
    $file = isset($_FILES['file']) ? $_FILES['file'] : null;

    $controller->uploadNote($data, $user['id'], $file);
} else {
    Response::error("Method not allowed.", 405);
}
?>
