<?php
require_once __DIR__ . '/../models/PeerLearning.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class PeerLearningController {
    
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
}
?>
