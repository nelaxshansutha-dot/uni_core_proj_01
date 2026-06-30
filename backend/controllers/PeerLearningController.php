<?php
require_once __DIR__ . '/../models/PeerLearning.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class PeerLearningController {
    
    public function createRequest($data, $student_id) {
        $missing = Validator::required(['course_code', 'topic'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        $model = new PeerLearning();
        $requestData = [
            'student_id' => $student_id,
            'course_code' => $data['course_code'],
            'topic' => $data['topic'],
            'description' => isset($data['description']) ? $data['description'] : null
        ];

        if ($model->createRequest($requestData)) {
            try {
                $db = (new Database())->getConnection();
                
                // Find student's courseID and std_year
                $stmt = $db->prepare("SELECT courseID, std_year FROM Student WHERE userID = ?");
                $stmt->execute([$student_id]);
                $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($studentInfo) {
                    $courseID = $studentInfo['courseID'];
                    $std_year = $studentInfo['std_year'];

                    // Find rep for that course and year
                    $stmt = $db->prepare("
                        SELECT cr.userID 
                        FROM Course_representative cr
                        JOIN Student s ON cr.enrollmentNo = s.enrollmentNo
                        WHERE cr.courseID = ? AND s.std_year = ?
                    ");
                    $stmt->execute([$courseID, $std_year]);
                    $reps = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($reps)) {
                        require_once __DIR__ . '/../models/Notification.php';
                        $notif = new Notification();
                        $title = "New Peer Learning Request";
                        $msg = "A new peer learning request has been submitted for " . $data['course_code'] . " on topic: " . $data['topic'];
                        $notif->createForUsers($reps, $title, $msg);
                    }
                }
            } catch (Exception $e) {
                // Fail silently so request creation succeeds even if notification fails
            }

            Response::success("Peer learning request submitted.");
        } else {
            Response::error("Failed to submit request.", 500);
        }
    }

    public function getStudentRequests($student_id) {
        $model = new PeerLearning();
        $requests = $model->getStudentRequests($student_id);
        Response::success("Requests retrieved", $requests);
    }
    
    public function getCourseRequests($course_code) {
        $model = new PeerLearning();
        $requests = $model->getGroupedRequestsByCourse($course_code);
        Response::success("Requests retrieved", $requests);
    }

    public function updateStatus($data, $rep_id) {
        $missing = Validator::required(['topic', 'course_code', 'status'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields.");
        }

        $model = new PeerLearning();
        
        if ($model->updateStatusByTopic($data['topic'], $data['course_code'], $data['status'], $rep_id)) {
            if ($data['status'] === 'approved') {
                try {
                    $db = (new Database())->getConnection();
                    
                    // We need any student from this topic to find the courseID and std_year
                    $stmt = $db->prepare("SELECT student_id FROM peer_learning_requests WHERE topic = ? AND course_code = ? LIMIT 1");
                    $stmt->execute([$data['topic'], $data['course_code']]);
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
                                $notif->createForUsers($peers, "Peer Learning Session Approved", "A peer learning session for " . $data['course_code'] . " on '" . $data['topic'] . "' has been approved.");
                            }
                            
                            // Notify Seniors (higher year)
                            $stmt = $db->prepare("SELECT userID FROM Student WHERE courseID = ? AND std_year > ?");
                            $stmt->execute([$courseID, $std_year]);
                            $seniors = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            if (!empty($seniors)) {
                                $notif->createForUsers($seniors, "Peer Learning Request (Seniors Needed)", "Year " . $std_year . " students need peer learning for " . $data['course_code'] . " (" . $data['topic'] . "). Can you help?");
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
