<?php
// Autoloader via Composer
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    spl_autoload_register(function ($class) {
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

use Middleware\AuthMiddleware;
use Config\Database;

try {
    // 1. Authenticate user
    $decoded = AuthMiddleware::authenticate(['course_representative']);
    $userID = $decoded->userID;

    $db = Database::getInstance()->getConnection();

    // 2. Get Rep details
    $stmt = $db->prepare("SELECT repID, enrollmentNo, courseID FROM course_representative WHERE userID = :uid LIMIT 1");
    $stmt->execute([':uid' => $userID]);
    $repData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$repData) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Course Representative not found."]);
        exit;
    }

    $repID = (int)$repData['repID'];
    $enrollmentNo = $repData['enrollmentNo'];

    // Get std_year from student record for rep context
    $stdYearStmt = $db->prepare("SELECT std_year FROM student WHERE enrollmentNo = :enr LIMIT 1");
    $stdYearStmt->execute([':enr' => $enrollmentNo]);
    $stdYear = $stdYearStmt->fetchColumn() ?: 1;

    // 3. Fetch all Peer Learning Requests assigned to this rep
    $queryRequests = "
        SELECT 
            plr.requestID, 
            plr.status, 
            plr.created_at,
            plr.std_year as request_year,
            plr.semester as request_semester,
            plr.courseUnitID,
            plr.courseUnitName,
            plr.description,
            s.enrollmentNo as studentEnrollment,
            CONCAT(u.fname, ' ', u.lname) as studentName
        FROM peer_learning_request plr
        JOIN student s ON plr.enrollmentNo = s.enrollmentNo
        JOIN users u ON s.userID = u.userID
        WHERE plr.repID = :repid
        ORDER BY plr.created_at DESC
    ";
    $stmtRequests = $db->prepare($queryRequests);
    $stmtRequests->execute([':repid' => $repID]);
    $requests = $stmtRequests->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch aggregated unit counts for pending requests
    $queryCounts = "
        SELECT 
            courseUnitID,
            courseUnitName as unitName,
            COUNT(DISTINCT enrollmentNo) as studentCount
        FROM peer_learning_request
        WHERE repID = :repid AND status = 'pending'
        GROUP BY courseUnitID, courseUnitName
    ";
    $stmtCounts = $db->prepare($queryCounts);
    $stmtCounts->execute([':repid' => $repID]);
    $unitCounts = $stmtCounts->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => [
            "rep_context" => [
                "std_year" => $stdYear
            ],
            "requests" => $requests,
            "unit_counts" => $unitCounts
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>
