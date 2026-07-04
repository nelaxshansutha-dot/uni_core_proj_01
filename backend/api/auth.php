<?php
require_once __DIR__ . '/../config/Cors.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../utils/Response.php';

Cors::enable();

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

$controller = new AuthController();

$data = json_decode(file_get_contents("php://input"), true);

if ($method === 'POST') {
    if ($action === 'register') {
        $controller->register($data);
    } else if ($action === 'login') {
        $controller->login($data);
    } else if ($action === 'verify-otp') {
        $controller->verifyOtp($data);
    } else if ($action === 'forgot-password') {
        $controller->forgotPassword($data);
    } else if ($action === 'verify-reset-otp') {
        $controller->verifyResetOtp($data);
    } else if ($action === 'reset-password') {
        $controller->resetPassword($data);
    } else if ($action === 'resend-otp') {
        $controller->resendOtp($data);
    } else if ($action === 'force-change-rep-password') {
        $controller->forceChangeRepPassword($data);
    } else if ($action === 'update-profile') {
        require_once __DIR__ . '/../utils/AuthMiddleware.php';
        $user = AuthMiddleware::authenticate();
        $controller->updateProfile($data, $user['id'], $user['role']);
    } else if ($action === 'logout') {
        $controller->logout();
    } else {
        Response::error("Invalid action.", 404);
    }
} else if ($method === 'GET') {
    if ($action === 'me') {
        $controller->me();
    } else {
        Response::error("Invalid action.", 404);
    }
} else {
    Response::error("Method not allowed.", 405);
}
?>
