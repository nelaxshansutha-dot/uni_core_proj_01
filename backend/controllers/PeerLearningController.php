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
        if ($model->updateStatus($data['id'], $data['status'], $rep_id)) {
            Response::success("Status updated.");
        } else {
            Response::error("Failed to update status. Only assigned rep can do this.", 403);
        }
    }
}
?>
