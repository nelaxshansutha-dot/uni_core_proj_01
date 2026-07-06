<?php
require_once __DIR__ . '/../config/Cors.php';
Cors::enable();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';


if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Method not allowed", 405);
}

try {
    // 1. Authenticate user
    $user = AuthMiddleware::authenticate();
    
    if ($user['role'] !== 'rep') {
        Response::error("Forbidden: Only Course Representatives can access this endpoint.", 403);
    }

    $db = (new Database())->getConnection();

    // 2. Get Rep's CourseID and StudyingYear
    $stmt = $db->prepare("
        SELECT cr.courseID, s.std_year
        FROM Course_representative cr
        JOIN Student s ON cr.enrollmentNo = s.enrollmentNo
        WHERE cr.userID = ?
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $repData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$repData || !$repData['courseID'] || !$repData['std_year']) {
        Response::error("Representative course/year mapping not found.", 400);
    }

    $courseId = $repData['courseID'];
    $year = $repData['std_year'];

    // 3. Isolate Peer Learning Requests
    // We want requests where the requesting student is in the same courseID and std_year
    $query = "
        SELECT 
            plr.requestID, 
            plr.courseUnitName, 
            plr.status, 
            plr.created_at,
            plr.std_year as request_year,
            plr.semester as request_semester,
            cu.courseUnitName as course_unit_name,
            s.enrollmentNo as studentEnrollment,
            CONCAT(u.fname, ' ', u.lname) as studentName
        FROM Peer_learning_request plr
        JOIN Student s ON plr.enrollmentNo = s.enrollmentNo
        JOIN Users u ON s.userID = u.userID
        LEFT JOIN Course_units cu ON plr.courseUnitID = cu.courseUnitID
        WHERE s.courseID = ? AND s.std_year = ?
        ORDER BY plr.created_at DESC
    ";

    $stmtRequests = $db->prepare($query);
    $stmtRequests->execute([$courseId, $year]);
    $requests = $stmtRequests->fetchAll(PDO::FETCH_ASSOC);

    // 4. Get Counts Grouped by Unit
    $countQuery = "
        SELECT 
            cu.courseUnitName as unitName,
            plr.courseUnitID,
            COUNT(DISTINCT plr.enrollmentNo) as studentCount
        FROM Peer_learning_request plr
        JOIN Student s ON plr.enrollmentNo = s.enrollmentNo
        LEFT JOIN Course_units cu ON plr.courseUnitID = cu.courseUnitID
        WHERE s.courseID = ? AND s.std_year = ?
        GROUP BY plr.courseUnitID, cu.courseUnitName
        ORDER BY studentCount DESC
    ";
    $stmtCounts = $db->prepare($countQuery);
    $stmtCounts->execute([$courseId, $year]);
    $unitCounts = $stmtCounts->fetchAll(PDO::FETCH_ASSOC);

    Response::success("Requests fetched successfully", [
        'rep_context' => [
            'courseID' => $courseId,
            'std_year' => $year
        ],
        'requests' => $requests,
        'unit_counts' => $unitCounts
    ]);

} catch (Exception $e) {
    Response::error("Server error: " . $e->getMessage(), 500);
}
?>
