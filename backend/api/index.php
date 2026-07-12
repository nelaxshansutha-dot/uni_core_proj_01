<?php
// Autoloader via Composer
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Basic fallback autoloader if composer isn't run yet
    spl_autoload_register(function ($class) {
        $prefix = '';
        $base_dir = __DIR__ . '/../';
        $file = $base_dir . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

// Handle CORS
\Config\Cors::handle();



// Ensure .env is loaded if it exists
if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Routing logic
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/uni_core_proj_01/backend/api', '', $uri); // Adjust base path as needed
$parts = explode('/', trim($uri, '/'));

$resource = $parts[0] ?? '';
$action = $parts[1] ?? null;
$id = $parts[2] ?? null;

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($resource) {
        case 'auth':
            $controller = new Controllers\AuthController();
            if ($action === 'register') $controller->register();
            elseif ($action === 'login') $controller->login();
            elseif ($action === 'logout') $controller->logout();
            elseif ($action === 'verify-otp') $controller->verifyOtp();
            elseif ($action === 'resend-otp') $controller->resendOtp();
            elseif ($action === 'forgot-password') $controller->forgotPassword();
            elseif ($action === 'verify-reset-otp') $controller->verifyResetOtp();
            elseif ($action === 'reset-password') $controller->resetPassword();
            elseif ($action === 'update-profile') $controller->updateProfile();
            else http_response_code(404);
            break;
            
        case 'profile':
            $controller = new Controllers\AuthController();
            $controller->getProfile();
            break;
            
        case 'lost-items':
            $controller = new Controllers\LostItemController();
            $controller->handleRequest($method, $action); // action holds ID if present
            break;

        case 'upload':
            $controller = new Controllers\UploadController();
            $controller->handleRequest($method);
            break;

        case 'marketplace':
            $controller = new Controllers\MarketplaceController();
            // simple parsing: if $action is numeric, it's ID. If $id is flag, it's action.
            $pid = is_numeric($action) ? $action : null;
            $act = $id === 'flag' ? 'flag' : (is_numeric($action) ? null : $action);
            $controller->handleRequest($method, $pid, $act);
            break;

        case 'notes':
            $controller = new Controllers\NotesController();
            $nid = is_numeric($action) ? $action : null;
            $act = is_numeric($action) ? $id : $action;
            $controller->handleRequest($method, $nid, $act);
            break;

        case 'peer-learning-requests':
            $controller = new Controllers\PeerLearningRequestController();
            $rid = is_numeric($action) ? $action : null;
            $act = is_numeric($action) ? $id : $action;
            $controller->handleRequest($method, $rid, $act);
            break;

        case 'notifications':
            $controller = new Controllers\NotificationController();
            if ($action === 'app') $controller->handleApp($method, $id);
            elseif ($action === 'sms') $controller->handleSms($method, $id);
            break;

        case 'courses':
            $controller = new Controllers\CourseController();
            $controller->handleCourses($method);
            break;

        case 'dashboard':
            $controller = new Controllers\DashboardController();
            if ($action === 'recent-activity') {
                $controller->getRecentActivity();
            }
            break;


        case 'notifications':
            $controller = new Controllers\NotificationController();
            $controller->getNotifications();
            break;

        case 'course-units':
            $controller = new Controllers\CourseController();
            $controller->handleCourseUnits($method, $action);
            break;

        case 'admin':
            $controller = new Controllers\AdminController();
            if ($action === 'dashboard') $controller->getDashboardStats();
            elseif ($action === 'users') {
                if ($method === 'GET') $controller->handleUsers($method, $id);
                elseif ($method === 'POST') $controller->createUser();
                elseif ($method === 'PUT') $controller->updateUser($id);
            }
            elseif ($action === 'users-status') $controller->toggleUserStatus($id);
            elseif ($action === 'search-students') $controller->searchStudents();
            elseif ($action === 'assign-rep') $controller->assignCourseRep();
            elseif ($action === 'content') $controller->getContent();
            elseif ($action === 'content-status') $controller->moderateContent();
            elseif ($action === 'reports') $controller->getReports();
            elseif ($action === 'reports-status') $controller->moderateReport();
            else {
                http_response_code(404);
                echo json_encode(["message" => "Admin endpoint not found"]);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(["message" => "Endpoint not found"]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
