<?php
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../config/Database.php';

require_once __DIR__ . '/BaseController.php';

class CourseController extends BaseController {
    public function getModules($courseID, $year, $semester, $userID) {
        if (empty($year) || empty($semester)) {
            Response::error("Missing year or semester.");
        }
        
       
        if ($userID) {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT courseID, std_year, enrollmentNo FROM Student WHERE userID = ?");
            $stmt->execute([$userID]);
            $studentRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($studentRow) {
                $courseID = $studentRow['courseID'];

              
                if (empty($courseID) && !empty($studentRow['enrollmentNo'])) {
                    $parts = explode('/', strtoupper(trim($studentRow['enrollmentNo'])));
                    if (count($parts) >= 2 && !empty($parts[1])) {
                        $code = $parts[1]; // e.g. "CST"
                        $stmt2 = $db->prepare("SELECT courseID FROM Course WHERE courseName LIKE ? LIMIT 1");
                        $stmt2->execute(['%' . $code . '%']);
                        $courseRow = $stmt2->fetch(PDO::FETCH_ASSOC);
                        if ($courseRow) {
                            $courseID = (int)$courseRow['courseID'];
                            // Update the student record so future lookups are fast
                            $db->prepare("UPDATE Student SET courseID = ? WHERE userID = ?")->execute([$courseID, $userID]);
                        }
                    }
                }

          
                if (empty($year) && !empty($studentRow['std_year'])) {
                    $year = $studentRow['std_year'];
                }
            }
        }

        if (empty($courseID)) {
            Response::error("Could not determine your course. Please update your profile with your course details.");
        }

        $model = new Course();
        $modules = $model->getModulesByCourseYearSemester($courseID, $year, $semester);
        Response::success("Modules retrieved successfully", $modules);
    }
}
?>
