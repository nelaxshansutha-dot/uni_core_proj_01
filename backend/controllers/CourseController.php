<?php
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../utils/Response.php';

require_once __DIR__ . '/BaseController.php';

class CourseController extends BaseController {
    public function getModules($courseID, $year, $semester, $userID) {
        if (empty($year) || empty($semester)) {
            Response::error("Missing year or semester.");
        }
        
        // If courseID is empty, extract it from the student's enrollment number
        if (empty($courseID) && $userID) {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT enrollmentNo FROM Student WHERE userID = ?");
            $stmt->execute([$userID]);
            $enrollmentNo = $stmt->fetchColumn();
            
            if ($enrollmentNo) {
                // Parse uwu/cst/23/088 to extract 'cst'
                $parts = explode('/', $enrollmentNo);
                if (count($parts) >= 2) {
                    $courseID = strtoupper(trim($parts[1]));
                }
            }
        }
        
        if (empty($courseID)) {
            Response::error("Could not determine your course from your enrollment number. Please provide a courseID.");
        }

        $model = new Course();
        $modules = $model->getModulesByCourseYearSemester($courseID, $year, $semester);
        Response::success("Modules retrieved successfully", $modules);
    }
}
?>
