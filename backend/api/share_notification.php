<?php
require_once __DIR__ . '/../config/Cors.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';

Cors::enable();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed", 405);
}

try {
    $user = AuthMiddleware::authenticate();
    
    if ($user['role'] !== 'rep') {
        Response::error("Forbidden: Only Course Representatives can access this endpoint.", 403);
    }

    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['action']) || !isset($data['request_id'])) {
        Response::error("Missing required parameters: action and request_id", 400);
    }

    $db = (new Database())->getConnection();

    // 1. Get Rep context and the Request details
    $stmtRep = $db->prepare("
        SELECT cr.courseID, s.std_year
        FROM Course_representative cr
        JOIN Student s ON cr.enrollmentNo = s.enrollmentNo
        WHERE cr.userID = ?
        LIMIT 1
    ");
    $stmtRep->execute([$user['id']]);
    $repData = $stmtRep->fetch(PDO::FETCH_ASSOC);

    if (!$repData) {
        Response::error("Representative profile not found.", 404);
    }

    $stmtReq = $db->prepare("
        SELECT plr.*, cu.courseUnitName as course_unit_name
        FROM Peer_learning_request plr
        LEFT JOIN Course_units cu ON plr.courseUnitID = cu.courseUnitID
        WHERE plr.requestID = ?
        LIMIT 1
    ");
    $stmtReq->execute([$data['request_id']]);
    $requestData = $stmtReq->fetch(PDO::FETCH_ASSOC);

    if (!$requestData) {
        Response::error("Peer learning request not found.", 404);
    }

    $courseId = $repData['courseID'];
    $currentYear = $repData['std_year'];
    
    $topic = $requestData['courseUnitName'] ?? 'N/A';
    $unitName = $requestData['course_unit_name'] ?? 'General Topic';
    $yearStr = $requestData['std_year'] ? "Year {$requestData['std_year']}" : '';
    $semStr = $requestData['semester'] ? "Semester {$requestData['semester']}" : '';

    $db->beginTransaction();

    if ($data['action'] === 'share_classmates') {
        // Share with classmates: Find all users who are students in the same course and year
        $stmtUsers = $db->prepare("
            SELECT userID FROM Student 
            WHERE courseID = ? AND std_year = ? AND userID != ?
        ");
        $stmtUsers->execute([$courseId, $currentYear, $user['id']]);
        $classmates = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);

        if (count($classmates) === 0) {
            $db->rollBack();
            Response::success("No classmates found to notify.", ["notified_count" => 0]);
        }

        $message = "A new Peer Learning (Kuppy) session request has been created for {$unitName} ($topic).";

        $stmtInsert = $db->prepare("
            INSERT INTO app_notification (SenderID, ReceiverID, NotificationMessage)
            VALUES (?, ?, ?)
        ");

        foreach ($classmates as $receiverId) {
            $stmtInsert->execute([$user['id'], $receiverId, $message]);
        }

        $db->commit();
        Response::success("Successfully shared with classmates.", ["notified_count" => count($classmates)]);

    } else if ($data['action'] === 'forward_seniors') {
        // Forward to Seniors: Find reps in same course but std_year = currentYear + 1
        $targetYear = $currentYear + 1;
        
        $stmtSeniors = $db->prepare("
            SELECT cr.userID 
            FROM Course_representative cr
            JOIN Student s ON cr.enrollmentNo = s.enrollmentNo
            WHERE cr.courseID = ? AND s.std_year = ? AND cr.userID != ?
        ");
        $stmtSeniors->execute([$courseId, $targetYear, $user['id']]);
        $seniors = $stmtSeniors->fetchAll(PDO::FETCH_COLUMN);

        if (count($seniors) === 0) {
            $db->rollBack();
            Response::success("No senior representatives found for year {$targetYear}.", ["notified_count" => 0]);
        }

        $message = "Junior Rep Request: Please help arrange a Kuppy session for {$unitName} ($topic) - $yearStr $semStr.";

        $stmtInsert = $db->prepare("
            INSERT INTO app_notification (SenderID, ReceiverID, NotificationMessage)
            VALUES (?, ?, ?)
        ");

        foreach ($seniors as $receiverId) {
            $stmtInsert->execute([$user['id'], $receiverId, $message]);
        }

        $db->commit();
        Response::success("Successfully forwarded to senior reps.", ["notified_count" => count($seniors)]);

    } else {
        $db->rollBack();
        Response::error("Invalid action specified.", 400);
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    Response::error("Server error: " . $e->getMessage(), 500);
}
?>
