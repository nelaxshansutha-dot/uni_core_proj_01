<?php
require_once __DIR__ . '/../config/Cors.php';
Cors::enable();
require_once __DIR__ . '/../controllers/CourseController.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';


$method = $_SERVER['REQUEST_METHOD'];
$controller = new CourseController();

$user = AuthMiddleware::authenticate();

if ($method === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    if ($action === 'modules') {
        $courseID = isset($_GET['courseID']) ? $_GET['courseID'] : '';
        $year = isset($_GET['year']) ? $_GET['year'] : '';
        $semester = isset($_GET['semester']) ? $_GET['semester'] : '';
        
        $controller->getModules($courseID, $year, $semester);
    } else {
        Response::error("Invalid action.", 400);
    }
} else {
    Response::error("Method not allowed.", 405);
}
?>
