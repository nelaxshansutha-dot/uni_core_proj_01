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
            // Send App Notifications to Course Representatives who have peer_learning_app_notification enabled
            try {
                $db = (new Database())->getConnection();
                // Find all representatives with peer_learning_app_notification enabled
                $stmt = $db->prepare("SELECT id FROM users WHERE role = 'rep' AND peer_learning_app_notification = 1");
                $stmt->execute();
                $reps = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($reps)) {
                    require_once __DIR__ . '/../models/Notification.php';
                    $notif = new Notification();
                    $title = "New Peer Learning Request";
                    $msg = "A new peer learning request has been submitted for " . $data['course_code'] . " on topic: " . $data['topic'];
                    $notif->createForUsers($reps, $title, $msg);
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
        $requests = $model->getRequestsByCourse($course_code);
        Response::success("Requests retrieved", $requests);
    }

    public function updateStatus($data, $rep_id) {
        $missing = Validator::required(['id', 'status'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields.");
        }

        $model = new PeerLearning();
        
        // Fetch request info before updating status so we can notify the correct student
        $reqInfo = null;
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT student_id, course_code, topic FROM peer_learning_requests WHERE id = ?");
            $stmt->execute([$data['id']]);
            $reqInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Ignore fetch error
        }

        if ($model->updateStatus($data['id'], $data['status'], $rep_id)) {
            // Send App Notification to the student if peer_learning_app_notification is enabled
            if ($reqInfo) {
                try {
                    $stmt = $db->prepare("SELECT peer_learning_app_notification FROM users WHERE id = ?");
                    $stmt->execute([$reqInfo['student_id']]);
                    $pref = $stmt->fetchColumn();

                    if ($pref == 1) {
                        require_once __DIR__ . '/../models/Notification.php';
                        $notif = new Notification();
                        $title = "Peer Learning Request Updated";
                        $msg = "Your peer learning request for " . $reqInfo['course_code'] . " on '" . $reqInfo['topic'] . "' has been " . $data['status'] . ".";
                        $notif->createForUser($reqInfo['student_id'], $title, $msg);
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
