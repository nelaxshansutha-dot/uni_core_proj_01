<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/PeerLearning.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

require_once __DIR__ . '/../models/CourseRep.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/BaseController.php';

class PeerLearningController extends BaseController {
    
    public function createRequest($data, $student_id) {
        $missing = Validator::required(['courseUnitID', 'courseUnitName'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        try {
            $db = (new Database())->getConnection();
            
            // 1. Get the student's enrollmentNo, courseID, std_year
            $stmt = $db->prepare("SELECT enrollmentNo, courseID, std_year FROM Student WHERE userID = ?");
            $stmt->execute([$student_id]);
            $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$studentInfo) {
                Response::error("Student record not found.", 404);
            }

            $enrollmentNo = $studentInfo['enrollmentNo'];
            $studentCourseID = $studentInfo['courseID'];
            $std_year = $studentInfo['std_year'] ? $studentInfo['std_year'] : 1;

            // Look up the courseID of the course unit being requested
            $stmt = $db->prepare("SELECT courseID FROM Course_units WHERE courseUnitID = ?");
            $stmt->execute([$data['courseUnitID']]);
            $unitCourseID = $stmt->fetchColumn();
            
            $targetCourseID = $unitCourseID ? $unitCourseID : $studentCourseID;

            // 2. Find the course rep for this course and year
            $stmt = $db->prepare("
                SELECT cr.repID, cr.userID 
                FROM Course_representative cr
                JOIN Student s ON cr.enrollmentNo = s.enrollmentNo
                WHERE cr.courseID = ? AND s.std_year = ?
                LIMIT 1
            ");
            $stmt->execute([$targetCourseID, $std_year]);
            $repInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            // Fallback: If no specific rep is found, just get ANY rep so the request doesn't fail
            if (!$repInfo) {
                $stmt = $db->query("SELECT repID, userID FROM Course_representative LIMIT 1");
                $repInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$repInfo) {
                Response::error("No course representative found in the system.", 400);
            }

            $repID = $repInfo['repID'];

            // 3. Create the request
            $model = new PeerLearning();
            $requestData = [
                'repID' => $repID,
                'enrollmentNo' => $enrollmentNo,
                'courseUnitID' => $data['courseUnitID'],
                'std_year' => $std_year ? $std_year : 1, // Fallback to 1 if not set
                'semester' => isset($data['semester']) ? $data['semester'] : 1,
                'courseUnitName' => $data['courseUnitName']
            ];

            if ($model->createRequest($requestData)) {
                // Try sending notification
                try {
                    require_once __DIR__ . '/../models/Notification.php';
                    $notif = new Notification();
                    $title = "New Course Unit Request";
                    $msg = "A student requested the unit " . $data['courseUnitName'] . " (" . $data['courseUnitID'] . ")";
                    $notif->createForUsers([$repInfo['userID']], $title, $msg);
                } catch (Exception $e) {
                    // Fail silently
                }
                Response::success("Request submitted successfully.");
            } else {
                Response::error("Failed to submit request.", 500);
            }
        } catch (PDOException $e) {
            Response::error("Database error: " . $e->getMessage(), 500);
        }
    }

    public function getStudentRequests($student_id) {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT enrollmentNo FROM Student WHERE userID = ?");
        $stmt->execute([$student_id]);
        $enrollmentNo = $stmt->fetchColumn();

        if ($enrollmentNo) {
            $model = new PeerLearning();
            $requests = $model->getStudentRequests($enrollmentNo);
            Response::success("Requests retrieved", $requests);
        } else {
            Response::error("Student record not found", 404);
        }
    }
    
    public function getCourseRequests($courseUnitID) {
        $model = new PeerLearning();
        $requests = $model->getGroupedRequestsByCourse($courseUnitID);
        Response::success("Requests retrieved", $requests);
    }

    public function updateStatus($data, $rep_id) {
        $missing = Validator::required(['courseUnitName', 'courseUnitID', 'status'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields.");
        }

        $model = new PeerLearning();
        
        if ($model->updateStatusByTopic($data['courseUnitName'], $data['courseUnitID'], $data['status'], $rep_id)) {
            if ($data['status'] === 'approved') {
                try {
                    $db = (new Database())->getConnection();
                    
                    // We need any student from this topic to find the courseID and std_year
                    $stmt = $db->prepare("SELECT student_id FROM peer_learning_requests WHERE courseUnitName = ? AND courseUnitID = ? LIMIT 1");
                    $stmt->execute([$data['courseUnitName'], $data['courseUnitID']]);
                    $sampleStudentId = $stmt->fetchColumn();

                    if ($sampleStudentId) {
                        $stmt = $db->prepare("SELECT courseID, std_year FROM Student WHERE userID = ?");
                        $stmt->execute([$sampleStudentId]);
                        $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($studentInfo) {
                            $courseID = $studentInfo['courseID'];
                            $std_year = $studentInfo['std_year'];
                            
                            require_once __DIR__ . '/../models/Notification.php';
                            $notif = new Notification();
                            
                            // Notify Peers (same year)
                            $stmt = $db->prepare("SELECT userID FROM Student WHERE courseID = ? AND std_year = ?");
                            $stmt->execute([$courseID, $std_year]);
                            $peers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            if (!empty($peers)) {
                                $notif->createForUsers($peers, "Peer Learning Session Approved", "A peer learning session for " . $data['courseUnitID'] . " on '" . $data['courseUnitName'] . "' has been approved.");
                            }
                            
                            // Notify Seniors (higher year)
                            $stmt = $db->prepare("SELECT userID FROM Student WHERE courseID = ? AND std_year > ?");
                            $stmt->execute([$courseID, $std_year]);
                            $seniors = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            if (!empty($seniors)) {
                                $notif->createForUsers($seniors, "Peer Learning Request (Seniors Needed)", "Year " . $std_year . " students need peer learning for " . $data['courseUnitID'] . " (" . $data['courseUnitName'] . "). Can you help?");
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Fail silently so status update succeeds
                }
            }

            Response::success("Status updated.");
        } else {
            Response::error("Failed to update status. Only assigned rep can do this.", 403);
        }
    }

    public function getRepDashboardData($userId) {
        try {
            require_once __DIR__ . '/../models/CourseRep.php';
            $repModel = new CourseRep();
            $repData = $repModel->getRepCourseAndYear($userId);
            
            if (!$repData || !$repData['courseID'] || !$repData['std_year']) {
                $this->errorResponse("Representative course/year mapping not found.", 400);
            }

            $courseId = $repData['courseID'];
            $year = $repData['std_year'];

            $model = new PeerLearning();
            $requests = $model->getRepDashboardRequests($courseId, $year);
            $unitCounts = $model->getRepDashboardUnitCounts($courseId, $year);

            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Requests fetched successfully',
                'data' => [
                    'rep_context' => [
                        'courseID' => $courseId,
                        'std_year' => $year
                    ],
                    'requests' => $requests,
                    'unit_counts' => $unitCounts
                ]
            ]);
        } catch (Exception $e) {
            $this->errorResponse("Server error: " . $e->getMessage(), 500);
        }
    }

    public function shareRequest($data, $userId) {
        try {
            if (!isset($data['action']) || !isset($data['request_id'])) {
                $this->errorResponse("Missing required parameters: action and request_id", 400);
            }

            require_once __DIR__ . '/../models/CourseRep.php';
            $repModel = new CourseRep();
            $repData = $repModel->getRepCourseAndYear($userId);

            if (!$repData) {
                $this->errorResponse("Representative profile not found.", 404);
            }

            $model = new PeerLearning();
            $requestData = $model->findById($data['request_id']);

            if (!$requestData) {
                $this->errorResponse("Peer learning request not found.", 404);
            }

            $courseId = $repData['courseID'];
            $currentYear = $repData['std_year'];
            
            $topic = $requestData['courseUnitName'] ?? 'N/A';
            $yearStr = $requestData['std_year'] ? "Year {$requestData['std_year']}" : '';
            $semStr = $requestData['semester'] ? "Semester {$requestData['semester']}" : '';

            require_once __DIR__ . '/../models/Notification.php';
            $notifModel = new Notification();

            if ($data['action'] === 'share_classmates') {
                $db = (new Database())->getConnection();
                $stmtUsers = $db->prepare("
                    SELECT userID FROM Student 
                    WHERE courseID = ? AND std_year = ? AND userID != ?
                ");
                $stmtUsers->execute([$courseId, $currentYear, $userId]);
                $classmates = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);

                if (empty($classmates)) {
                    $this->jsonResponse(["status" => "success", "message" => "No classmates found to notify.", "data" => ["notified_count" => 0]]);
                }

                $title = "New Kuppy Session Request";
                $message = "A new Peer Learning (Kuppy) session request has been created for ($topic).";
                $notifModel->createForUsers($classmates, $title, $message);

                $this->jsonResponse(["status" => "success", "message" => "Successfully shared with classmates.", "data" => ["notified_count" => count($classmates)]]);

            } else if ($data['action'] === 'forward_seniors') {
                $targetYear = $currentYear + 1;
                $db = (new Database())->getConnection();
                $stmtSeniors = $db->prepare("
                    SELECT cr.userID 
                    FROM Course_representative cr
                    JOIN Student s ON cr.enrollmentNo = s.enrollmentNo
                    WHERE cr.courseID = ? AND s.std_year = ? AND cr.userID != ?
                ");
                $stmtSeniors->execute([$courseId, $targetYear, $userId]);
                $seniors = $stmtSeniors->fetchAll(PDO::FETCH_COLUMN);

                if (empty($seniors)) {
                    $this->jsonResponse(["status" => "success", "message" => "No senior representatives found for year {$targetYear}.", "data" => ["notified_count" => 0]]);
                }

                $title = "Junior Rep Request";
                $message = "Please help arrange a Kuppy session for ($topic) - $yearStr $semStr.";
                $notifModel->createForUsers($seniors, $title, $message);

                $this->jsonResponse(["status" => "success", "message" => "Successfully forwarded to senior reps.", "data" => ["notified_count" => count($seniors)]]);

            } else {
                $this->errorResponse("Invalid action specified.", 400);
            }

        } catch (Exception $e) {
            $this->errorResponse("Server error: " . $e->getMessage(), 500);
        }
    }
}
?>