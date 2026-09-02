<?php
namespace Controllers;
use Models\PeerLearningRequest;
use Middleware\AuthMiddleware;
use DAO\PeerLearningRequestDAO;
use DAO\CourseRepresentativeDAO;
use DAO\StudentDAO;
use DAO\CourseUnitDAO;

class PeerLearningRequestController {

    /**
     * Parse enrollment number to extract batch year (2-digit).
     * Format: UWU/CST/YY/NNN → returns YY as int
     */
    private function getBatchYear(string $enrollmentNo): ?int {
        $parts = explode('/', strtoupper(trim($enrollmentNo)));
        if (count($parts) < 3) return null;
        $batch = (int)$parts[2];
        return $batch > 0 ? $batch : null;
    }

    /**
     * Parse enrollment number to extract course code.
     * Format: UWU/CST/YY/NNN → returns "CST"
     */
    private function getCourseCode(string $enrollmentNo): ?string {
        $parts = explode('/', strtoupper(trim($enrollmentNo)));
        return $parts[1] ?? null;
    }

    /**
     * Find the repID for a given student based on exact courseID and batch year match.
     */
    private function findRepForStudent(string $enrollmentNo): ?int {
        $studentBatchYear = \Models\Student::extractBatchYear($enrollmentNo);
        if (!$studentBatchYear) return null;

        $studentDAO = new StudentDAO();
        $student = $studentDAO->getStudentByEnrollmentNo($enrollmentNo);
        if (!$student || empty($student['courseID'])) return null;

        $studentCourseID = $student['courseID'];

        $repDAO = new CourseRepresentativeDAO();
        $activeReps = $repDAO->getActiveRepsByCourseID($studentCourseID);
        
        foreach ($activeReps as $rep) {
            $repBatchYear = \Models\Student::extractBatchYear($rep['enrollmentNo']);
            if ($repBatchYear === $studentBatchYear) {
                return (int)$rep['repID'];
            }
        }
        
        return null;
    }

    public function handleRequest($method, $id = null, $action = null) {
        $decoded = AuthMiddleware::authenticate(['student', 'course_representative']);

        // ─── GET ──────────────────────────────────────────────────────────
        if ($method === 'GET') {
            $role = $decoded->role ?? 'student';

            if ($role === 'course_representative') {
                // Rep sees ALL requests assigned to them, grouped by courseUnit
                // showing the count of students who requested each module
                $repDAO = new CourseRepresentativeDAO();
                $repID = $repDAO->getRepIdByUserId($decoded->userID);

                if (!$repID) {
                    echo json_encode(['status' => 'success', 'data' => []]);
                    return;
                }

                $plrDAO = new PeerLearningRequestDAO();
                $grouped = $plrDAO->getGroupedRequestsForRep($repID);

                // Convert grouped descriptions into arrays and filter out "General unit request" if there are specific ones
                foreach ($grouped as &$row) {
                    if ($row['descriptions']) {
                        $allDesc = array_filter(explode('|||', $row['descriptions']));
                        $row['descriptions_list'] = array_values($allDesc);
                    } else {
                        $row['descriptions_list'] = [];
                    }
                    unset($row['descriptions']); // clean up the raw string
                }

                echo json_encode(['status' => 'success', 'data' => $grouped]);

            } else {
                // Student sees their own requests
                $enrollmentNo = $decoded->enrollmentNo ?? null;
                if (!$enrollmentNo) {
                    echo json_encode(['status' => 'success', 'data' => []]);
                    return;
                }
                
                $plrDAO = new PeerLearningRequestDAO();
                $rows = $plrDAO->getRequestsByEnrollmentNo($enrollmentNo);
                echo json_encode(['status' => 'success', 'data' => $rows]);
            }
            return;
        }

        // ─── POST — Student submits a request ─────────────────────────────
        if ($method === 'POST') {
            $data         = json_decode(file_get_contents("php://input"), true) ?? [];
            
            $validator = new \Utils\Validator($data);
            $validator->validate([
                'courseUnitID' => 'required|string|maxLength:20',
                'courseUnitName' => 'nullable|string|maxLength:200',
                'semester' => 'nullable|integer|min:1|max:2',
                'description' => 'required|string|maxLength:1000'
            ]);

            if (!$validator->passes()) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $validator->getFirstError(),
                    'errors' => $validator->getErrors()
                ]);
                return;
            }
            
            $enrollmentNo = $decoded->enrollmentNo ?? null;

            if (!$enrollmentNo) {
                echo json_encode(['status' => 'error', 'message' => 'Enrollment number not found in token.']);
                return;
            }

            // Auto-detect the rep for this student's batch + course
            $repID = $this->findRepForStudent($enrollmentNo);
            if (!$repID) {
                echo json_encode(['status' => 'error', 'message' => 'No Course Representative assigned for your batch']);
                return;
            }

            // Auto-detect student's academic year from enrollment number
            \Controllers\CourseController::getAcademicYearFromEnrollment($enrollmentNo);
            $currentYear  = (int)date('Y');
            $currentMonth = (int)date('m');
            $academicYear = ($currentMonth < 10) ? $currentYear - 1 : $currentYear;
            $batchYear    = $this->getBatchYear($enrollmentNo);
            $stdYear      = $batchYear ? ($academicYear % 100 - $batchYear) : null;

            $courseUnitID   = $data['courseUnitID']   ?? null;
            $courseUnitName = $data['courseUnitName'] ?? null;
            $semester       = $data['semester']       ?? null;
            $description    = $data['description']    ?? 'General unit request';

            if (!$courseUnitID && !$courseUnitName) {
                echo json_encode(['status' => 'error', 'message' => 'Course unit is required.']);
                return;
            }

            // If courseUnitName not given, look it up
            if (!$courseUnitName && $courseUnitID) {
                $cuDAO = new CourseUnitDAO();
                $cuRow = $cuDAO->view($courseUnitID);
                $courseUnitName = $cuRow['courseUnitName'] ?? $courseUnitID;
                if (!$semester) $semester = $cuRow['semester'] ?? null;
            }

            // Check for duplicate request (same student + same courseUnit + pending)
            $plrDAO = new PeerLearningRequestDAO();
            if ($plrDAO->hasPendingRequest($enrollmentNo, $courseUnitID)) {
                echo json_encode(['status' => 'error', 'message' => 'You have already submitted a pending request for this module.']);
                return;
            }

            $model = new PeerLearningRequest();
            $model->hydrateFromRequest([
                'courseUnitID' => $courseUnitID,
                'courseUnitName' => $courseUnitName,
                'semester' => $semester,
                'description' => $description,
                'std_year' => $stdYear
            ]);
            $model->setEnrollmentNo($enrollmentNo);
            $model->setRepID($repID);
            $ok = $model->submit();

            if ($ok) {
                echo json_encode(['status' => 'success', 'message' => 'Your request was sent to your course representative.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to submit request.']);
            }
            return;
        }

        // ─── PUT — Rep updates status ──────────────────────────────────────
        if ($method === 'PUT') {
            AuthMiddleware::authenticate(['course_representative']);
            $data = json_decode(file_get_contents("php://input"), true) ?? [];

            $validator = new \Utils\Validator($data);
            $validator->validate([
                'courseUnitID' => 'required|string|maxLength:20',
                'courseUnitName' => 'nullable|string|maxLength:200',
                'status' => 'required|string|in:approved,rejected,completed,broadcast_help',
                'message' => 'nullable|string|maxLength:1000'
            ]);

            if (!$validator->passes()) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $validator->getFirstError(),
                    'errors' => $validator->getErrors()
                ]);
                return;
            }

            $courseUnitID = $data['courseUnitID'] ?? null;
            $status       = $data['status']       ?? null;

            // Get rep's repID
            $repDAO = new CourseRepresentativeDAO();
            $repID = $repDAO->getRepIdByUserId($decoded->userID);

            if (!$repID) {
                echo json_encode(['status' => 'error', 'message' => 'Rep ID not found.']);
                return;
            }

            $plrDAO = new PeerLearningRequestDAO();

            // Special handling for broadcast_help
            if ($status === 'broadcast_help') {
                $ok = $plrDAO->updatePendingStatusForRepAndCourse($repID, $courseUnitID, 'completed');

                if ($ok) {
                    $customMsg = $data['message'] ?? '';
                    $this->dispatchBroadcastNotifications($repID, $courseUnitID, $customMsg);
                }

                echo json_encode(['status' => $ok ? 'success' : 'error']);
                return;
            }

            // Update ALL pending requests for this courseUnit assigned to this rep
            $ok = $plrDAO->updatePendingStatusForRepAndCourse($repID, $courseUnitID, $status);

            // If approved, notify all the requesting students
            if ($ok && $status === 'approved') {
                $this->dispatchApprovalNotifications($repID, $courseUnitID);
            }

            echo json_encode(['status' => $ok ? 'success' : 'error']);
            return;
        }

        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    }

    /**
     * Notify all students whose request for this courseUnit was just approved.
     */
    private function dispatchApprovalNotifications(int $repID, string $courseUnitID): void {
        $cuDAO = new CourseUnitDAO();
        $cuRow = $cuDAO->view($courseUnitID);
        $unitName = $cuRow ? $cuRow['courseUnitName'] : $courseUnitID;

        $message = "Your peer learning request for \"{$unitName}\" has been approved by your Course Representative!";

        $plrDAO = new PeerLearningRequestDAO();
        $students = $plrDAO->getStudentsToNotifyForApprovedRequest($repID, $courseUnitID);

        foreach ($students as $row) {
            $appNotification = new \Models\AppNotification();
            $appNotification
                ->setRepID($repID)
                ->setEnrollmentNo($row['enrollmentNo'])
                ->setMessage($message);
            $appNotification->send();
        }
    }

    /**
     * Notify all seniors and batch mates for help.
     */
    private function dispatchBroadcastNotifications(int $repID, string $courseUnitID, string $customMsg = ''): void {
        $cuDAO = new CourseUnitDAO();
        $cuRow = $cuDAO->view($courseUnitID);
        $unitName = $cuRow ? $cuRow['courseUnitName'] : $courseUnitID;

        $repDAO = new CourseRepresentativeDAO();
        $rep_id_string = $repDAO->getRepIdStringByRepId($repID);
        if (!$rep_id_string) return;

        // Extract course code and batch year from rep_id_string
        $parts = explode('/', strtoupper(trim($rep_id_string)));
        if (count($parts) < 3) return;
        $courseCode = $parts[1]; // CST
        $batchYear = (int)$parts[2]; // 23

        $message = !empty($customMsg) 
            ? $customMsg 
            : "Students in Batch {$batchYear} have requested Peer Learning for \"{$unitName}\". If you can help, please reach out to the Course Rep!";

        // Find all students in this course with batch year <= rep's batch year
        $studentDAO = new StudentDAO();
        $students = $studentDAO->getEnrollmentsForRepCourseWithNotification($repID);

        foreach ($students as $row) {
            $studentBatch = $this->getBatchYear($row['enrollmentNo']);
            if ($studentBatch !== null && $studentBatch <= $batchYear) {
                $appNotification = new \Models\AppNotification();
                $appNotification
                    ->setRepID($repID)
                    ->setEnrollmentNo($row['enrollmentNo'])
                    ->setMessage($message);
                $appNotification->send();
            }
        }
    }
}
