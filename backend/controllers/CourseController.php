<?php
namespace Controllers;
use Models\Course;
use Models\CourseUnit;
use Middleware\AuthMiddleware;

class CourseController {

    /**
     * Parse the enrollment number to extract the batch year and calculate the student's academic year.
     * Format: UWU/CST/YY/NNN
     * Logic: academic_year = (current_academic_year_2digit) - batch_year_2digit
     * e.g. batch 24 → 25 - 24 = Year 1, batch 23 → Year 2, batch 22 → Year 3, batch 21 → Year 4
     */
    public static function getAcademicYearFromEnrollment(string $enrollmentNo): ?int {
        // e.g. UWU/CST/23/088 → parts[2] = "23"
        $parts = explode('/', strtoupper(trim($enrollmentNo)));
        if (count($parts) < 3) return null;

        $batchYear = (int)$parts[2]; // e.g. 23
        if ($batchYear <= 0) return null;

        // Current academic year (Sri Lankan universities: Oct–Sep cycle)
        // If month < 10, we're still in the academic year that started last year
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');
        $academicYear = ($currentMonth < 10) ? $currentYear - 1 : $currentYear;
        $academicYearShort = $academicYear % 100; // e.g. 2025 → 25

        $stdYear = $academicYearShort - $batchYear;
        if ($stdYear < 1 || $stdYear > 4) return null;

        return $stdYear;
    }

    /**
     * Parse the enrollment number to extract the course name.
     * e.g. UWU/CST/23/088 → "CST"
     */
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
            // Only semester is needed as input — year is auto-detected from enrollment number
            $semester = $_GET['semester'] ?? '';
            $userID   = $decoded->userID;

            $db = \Config\Database::getInstance()->getConnection();

            // Get student's enrollment number and courseID
            $stmt = $db->prepare("SELECT courseID, enrollmentNo FROM student WHERE userID = :uid");
            $stmt->execute([':uid' => $userID]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $courseID     = $row['courseID']     ?? null;
            $enrollmentNo = $row['enrollmentNo'] ?? '';

            // Auto-detect courseID from enrollment number prefix (UWU/CST/... → CST → courseID 1)
            if (!$courseID && !empty($enrollmentNo)) {
                $courseCode = self::getCourseCodeFromEnrollment($enrollmentNo);
                if ($courseCode === 'CST') {
                    $courseID = 1;
                    $db->prepare("UPDATE student SET courseID = 1 WHERE userID = :uid")->execute([':uid' => $userID]);
                }
            }

            if (!$courseID) {
                echo json_encode(['status' => 'error', 'message' => 'No course is associated with your account. Please contact your admin.']);
                return;
            }

            // Auto-detect academic year from enrollment number
            $stdYear = self::getAcademicYearFromEnrollment($enrollmentNo);
            if (!$stdYear) {
                echo json_encode(['status' => 'error', 'message' => 'Could not determine your academic year from enrollment number: ' . $enrollmentNo]);
                return;
            }

            // Persist detected year
            $db->prepare("UPDATE student SET std_year = :yr WHERE userID = :uid AND (std_year IS NULL OR std_year = 0)")
               ->execute([':yr' => $stdYear, ':uid' => $userID]);

            // Fetch modules for this course, detected year, and selected semester
            $stmt = $db->prepare("SELECT courseUnitID, courseUnitName FROM course_units WHERE courseID = :cid AND academicYear = :year AND semester = :sem");
            $stmt->execute([':cid' => $courseID, ':year' => $stdYear, ':sem' => $semester]);
            $modules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

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

        echo json_encode(['success' => true, 'data' => []]);
    }
}
