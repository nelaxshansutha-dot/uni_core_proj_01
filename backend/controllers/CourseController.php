<?php
namespace Controllers;
use Models\Course;
use Models\CourseUnit;
use Middleware\AuthMiddleware;

class CourseController {
    public function handleCourses($method) {
        $decoded = AuthMiddleware::authenticate();
        // Return courses logic (skeleton for now)
        echo json_encode(['success' => true, 'data' => []]);
    }

    public function handleCourseUnits($method, $action) {
        $decoded = AuthMiddleware::authenticate();
        
        if ($method === 'GET' && $action === 'my-modules') {
            $year = $_GET['year'] ?? '';
            $semester = $_GET['semester'] ?? '';
            $userID = $decoded->userID;
            
            $db = \Config\Database::getInstance()->getConnection();
            
            // Extract the user's course ID automatically
            $stmt = $db->prepare("SELECT courseID FROM student WHERE userID = :uid");
            $stmt->execute([':uid' => $userID]);
            $courseID = $stmt->fetchColumn();
            
            if (!$courseID) {
                echo json_encode(['status' => 'error', 'message' => 'No course associated with this user.']);
                return;
            }
            
            // Fetch relevant modules for their course, year, and semester
            $stmt = $db->prepare("SELECT courseUnitID, courseUniName AS courseUnitName FROM course_units WHERE courseID = :cid AND academicYear = :year AND semester = :sem");
            $stmt->execute([':cid' => $courseID, ':year' => $year, ':sem' => $semester]);
            $modules = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'success', 'data' => $modules]);
            return;
        }

        echo json_encode(['success' => true, 'data' => []]);
    }
}
