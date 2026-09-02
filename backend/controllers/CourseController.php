<?php
namespace Controllers;
use Models\Course;
use Models\CourseUnit;
use Middleware\AuthMiddleware;

class CourseController {


    public static function getAcademicYearFromEnrollment(string $enrollmentNo): ?int {
        // e.g. UWU/CST/23/088 → parts[2] = "23"
        $parts = explode('/', strtoupper(trim($enrollmentNo)));
        if (count($parts) < 3) return null;

        $batchYear = (int)$parts[2]; // e.g. 23
        if ($batchYear <= 0) return null;

        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');
        $academicYear = ($currentMonth < 10) ? $currentYear - 1 : $currentYear;
        $academicYearShort = $academicYear % 100; // e.g. 2025 → 25

        $stdYear = $academicYearShort - $batchYear;
        if ($stdYear < 1 || $stdYear > 4) return null;

        return $stdYear;
    }

 
    public static function getCourseCodeFromEnrollment(string $enrollmentNo): ?string {
        $parts = explode('/', strtoupper(trim($enrollmentNo)));
        return $parts[1] ?? null; // e.g. "CST"
    }

    public function handleCourses($method) {
        $decoded = AuthMiddleware::authenticate();
        echo json_encode(['success' => true, 'data' => []]);
    }

    public function handleCourseUnits($method, $action) {
        $decoded = AuthMiddleware::authenticate();

        if ($method === 'GET' && $action === 'my-modules') {
       
            $semester = $_GET['semester'] ?? '';
            $userID   = $decoded->userID;

            $validator = new \Utils\Validator(['semester' => $semester]);
            $validator->validate(['semester' => 'required|integer|min:1|max:2']);
            if (!$validator->passes()) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $validator->getFirstError(),
                    'errors' => $validator->getErrors()
                ]);
                return;
            }

            $studentDao = new \DAO\StudentDAO();
            $row = $studentDao->getStudentByUserId($userID);
            $courseID     = $row['courseID']     ?? null;
            $enrollmentNo = $row['enrollmentNo'] ?? '';

          
            if (!$courseID && !empty($enrollmentNo)) {
                $courseCode = self::getCourseCodeFromEnrollment($enrollmentNo);
                if ($courseCode === 'CST') {
                    $courseID = 1;
                    $studentDao->updateCourseId($userID, 1);
                }
            }

            if (!$courseID) {
                echo json_encode(['status' => 'error', 'message' => 'No course is associated with your account. Please contact your admin.']);
                return;
            }

          
            $stdYear = self::getAcademicYearFromEnrollment($enrollmentNo);
            if (!$stdYear) {
                echo json_encode(['status' => 'error', 'message' => 'Could not determine your academic year from enrollment number: ' . $enrollmentNo]);
                return;
            }

        
            $studentDao->updateYearIfNull($userID, $stdYear);

            // Fetch modules for this course, detected year, and selected semester
            $courseUnitDao = new \DAO\CourseUnitDAO();
            $modules = $courseUnitDao->getModulesForCourse($courseID, $stdYear, $semester);

            if (empty($modules)) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => "No modules found for Year {$stdYear}, Semester {$semester}. Please check with your admin."
                ]);
                return;
            }

            echo json_encode([
                'status'   => 'success',
                'data'     => $modules,
                'std_year' => $stdYear  // return detected year so frontend can display it
            ]);
            return;
        }

        if ($method === 'GET' && $action === 'all') {
            $courseUnitDao = new \DAO\CourseUnitDAO();
            echo json_encode(['status' => 'success', 'data' => $courseUnitDao->getAll()]);
            return;
        }

        echo json_encode(['success' => true, 'data' => []]);
    }
}
