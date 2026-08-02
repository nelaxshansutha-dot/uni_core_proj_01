<?php
namespace Controllers;
use Models\PeerLearningRequest;
use Middleware\AuthMiddleware;

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
     * Find the repID for a given course code and batch year.
     * Rep enrollment format: UWU/CST/YY/NNN → rep_id_string = rep_uwu/cst/YY/NNN
     * Match by same course code and same batch year (YY part).
     */
    private function findRepForStudent(string $enrollmentNo): ?int {
        $batchYear  = $this->getBatchYear($enrollmentNo);
        $courseCode = $this->getCourseCode($enrollmentNo);
        if (!$batchYear || !$courseCode) return null;

        $db = \Config\Database::getInstance()->getConnection();

        // Find a rep whose enrollment number has the same course code and batch year
        $pattern = '%/' . strtolower($courseCode) . '/' . $batchYear . '/%';
        $stmt = $db->prepare(
            "SELECT repID FROM course_representative 
             WHERE (LOWER(enrollmentNo) LIKE :pat OR LOWER(rep_id_string) LIKE :pat2)
             AND (is_active = 1 OR is_active IS NULL)
             ORDER BY repID DESC LIMIT 1"
        );
        $stmt->execute([
            ':pat'  => $pattern,
            ':pat2' => '%rep_uwu/' . strtolower($courseCode) . '/' . $batchYear . '/%'
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int)$row['repID'] : null;
    }

    public function handleRequest($method, $id = null, $action = null) {
        $decoded = AuthMiddleware::authenticate(['student', 'course_representative']);
        $db      = \Config\Database::getInstance()->getConnection();

        // ─── GET ──────────────────────────────────────────────────────────
        if ($method === 'GET') {
            $role = $decoded->role ?? 'student';

            if ($role === 'course_representative') {
                // Rep sees ALL requests assigned to them, grouped by courseUnit
                // showing the count of students who requested each module
                $repStmt = $db->prepare("SELECT repID FROM course_representative WHERE userID = :uid LIMIT 1");
                $repStmt->execute([':uid' => $decoded->userID]);
                $repRow  = $repStmt->fetch(\PDO::FETCH_ASSOC);
                $repID   = $repRow ? (int)$repRow['repID'] : null;

                if (!$repID) {
                    echo json_encode(['status' => 'success', 'data' => []]);
                    return;
                }

                // Group requests by courseUnitID + courseUnitName, count students
                $stmt = $db->prepare(
                    "SELECT 
                        courseUnitID,
                        courseUnitName,
                        MAX(semester) as semester,
                        MAX(std_year) as std_year,
                        CASE WHEN SUM(status = 'pending') > 0 THEN 'pending' ELSE MAX(status) END AS status,
                        COUNT(DISTINCT enrollmentNo) AS request_count,
                        GROUP_CONCAT(description ORDER BY created_at ASC SEPARATOR '|||') AS descriptions,
                        MAX(created_at) AS latest_request
                     FROM peer_learning_request
                     WHERE repID = :rid
                     GROUP BY courseUnitID, courseUnitName
                     ORDER BY request_count DESC, latest_request DESC"
                );
                $stmt->execute([':rid' => $repID]);
                $grouped = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // Convert grouped descriptions into arrays and filter out "General unit request" if there are specific ones
                foreach ($grouped as &$row) {
                    if ($row['descriptions']) {
                        $allDesc = array_filter(explode('|||', $row['descriptions']));
                        // Optional: filter out exact "General unit request" strings if you only want to show specific questions
                        // But let's keep all for now so the count matches.
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
                $stmt = $db->prepare(
                    "SELECT plr.*, cu.courseUnitName as unitLabel
                     FROM peer_learning_request plr
                     LEFT JOIN course_units cu ON plr.courseUnitID = cu.courseUnitID
                     WHERE plr.enrollmentNo = :enr
                     ORDER BY plr.created_at DESC"
                );
                $stmt->execute([':enr' => $enrollmentNo]);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                echo json_encode(['status' => 'success', 'data' => $rows]);
            }
            return;
        }

        // ─── POST — Student submits a request ─────────────────────────────
        if ($method === 'POST') {
            $data         = json_decode(file_get_contents("php://input"), true);
            $enrollmentNo = $decoded->enrollmentNo ?? null;

            if (!$enrollmentNo) {
                echo json_encode(['status' => 'error', 'message' => 'Enrollment number not found in token.']);
                return;
            }

            // Auto-detect the rep for this student's batch + course
            $repID = $this->findRepForStudent($enrollmentNo);

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
                $cu = $db->prepare("SELECT courseUnitName, semester FROM course_units WHERE courseUnitID = :id");
                $cu->execute([':id' => $courseUnitID]);
                $cuRow = $cu->fetch(\PDO::FETCH_ASSOC);
                $courseUnitName = $cuRow['courseUnitName'] ?? $courseUnitID;
                if (!$semester) $semester = $cuRow['semester'] ?? null;
            }

            // Check for duplicate request (same student + same courseUnit + pending)
            $dupCheck = $db->prepare(
                "SELECT requestID FROM peer_learning_request 
                 WHERE enrollmentNo = :enr AND courseUnitID = :cuid AND status = 'pending'"
            );
            $dupCheck->execute([':enr' => $enrollmentNo, ':cuid' => $courseUnitID]);
            if ($dupCheck->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'You have already submitted a pending request for this module.']);
                return;
            }

            $stmt = $db->prepare(
                "INSERT INTO peer_learning_request 
                 (courseUnitID, enrollmentNo, repID, std_year, courseUnitName, semester, description)
                 VALUES (:cuid, :enr, :repid, :year, :name, :sem, :desc)"
            );
            $ok = $stmt->execute([
                ':cuid'  => $courseUnitID,
                ':enr'   => $enrollmentNo,
                ':repid' => $repID,
                ':year'  => $stdYear,
                ':name'  => $courseUnitName,
                ':sem'   => $semester,
                ':desc'  => $description
            ]);

            if ($ok) {
                $repInfo = $repID
                    ? "Your request was sent to your course representative."
                    : "Request saved. No representative is currently assigned to your batch.";
                echo json_encode(['status' => 'success', 'message' => $repInfo]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to submit request.']);
            }
            return;
        }

        // ─── PUT — Rep updates status ──────────────────────────────────────
        if ($method === 'PUT') {
            AuthMiddleware::authenticate(['course_representative']);
            $data = json_decode(file_get_contents("php://input"), true);

            $courseUnitID = $data['courseUnitID'] ?? null;
            $status       = $data['status']       ?? null;

            if (!$courseUnitID || !$status) {
                echo json_encode(['status' => 'error', 'message' => 'courseUnitID and status are required.']);
                return;
            }

            // Get rep's repID
            $repStmt = $db->prepare("SELECT repID FROM course_representative WHERE userID = :uid LIMIT 1");
            $repStmt->execute([':uid' => $decoded->userID]);
            $repRow = $repStmt->fetch(\PDO::FETCH_ASSOC);
            $repID  = $repRow ? (int)$repRow['repID'] : null;

            // Special handling for broadcast_help
            if ($status === 'broadcast_help') {
                $stmt = $db->prepare(
                    "UPDATE peer_learning_request 
                     SET status = 'completed' 
                     WHERE repID = :rid AND courseUnitID = :cuid AND status = 'pending'"
                );
                $ok = $stmt->execute([':rid' => $repID, ':cuid' => $courseUnitID]);

                if ($ok) {
                    $this->dispatchBroadcastNotifications($repID, $courseUnitID, $db);
                }

                echo json_encode(['status' => $ok ? 'success' : 'error']);
                return;
            }

            // Update ALL pending requests for this courseUnit assigned to this rep
            $stmt = $db->prepare(
                "UPDATE peer_learning_request 
                 SET status = :status 
                 WHERE repID = :rid AND courseUnitID = :cuid AND status = 'pending'"
            );
            $ok = $stmt->execute([':status' => $status, ':rid' => $repID, ':cuid' => $courseUnitID]);

            // If approved, notify all the requesting students
            if ($ok && $status === 'approved') {
                $this->dispatchApprovalNotifications($repID, $courseUnitID, $db);
            }

            echo json_encode(['status' => $ok ? 'success' : 'error']);
            return;
        }
    }

    /**
     * Notify all students whose request for this courseUnit was just approved.
     */
    private function dispatchApprovalNotifications(int $repID, string $courseUnitID, $db): void {
        // Get courseUnitName
        $cu = $db->prepare("SELECT courseUnitName FROM course_units WHERE courseUnitID = :id");
        $cu->execute([':id' => $courseUnitID]);
        $unitName = $cu->fetchColumn() ?: $courseUnitID;

        $message = "Your peer learning request for \"{$unitName}\" has been approved by your Course Representative!";

        $students = $db->prepare(
            "SELECT plr.enrollmentNo 
             FROM peer_learning_request plr
             JOIN student s ON plr.enrollmentNo = s.enrollmentNo
             JOIN users u ON s.userID = u.userID
             WHERE plr.repID = :rid AND plr.courseUnitID = :cuid AND plr.status = 'approved'
             AND u.peer_learning_app_notification = 1"
        );
        $students->execute([':rid' => $repID, ':cuid' => $courseUnitID]);

        $ins = $db->prepare("INSERT INTO app_notification (repID, enrollmentNo, message) VALUES (:rid, :enr, :msg)");
        while ($row = $students->fetch(\PDO::FETCH_ASSOC)) {
            $ins->execute([':rid' => $repID, ':enr' => $row['enrollmentNo'], ':msg' => $message]);
        }
    }

    /**
     * Notify all seniors and batch mates for help.
     */
    private function dispatchBroadcastNotifications(int $repID, string $courseUnitID, $db): void {
        // Get courseUnitName
        $cu = $db->prepare("SELECT courseUnitName FROM course_units WHERE courseUnitID = :id");
        $cu->execute([':id' => $courseUnitID]);
        $unitName = $cu->fetchColumn() ?: $courseUnitID;

        // Get Rep's details (rep_id_string)
        $repStmt = $db->prepare("SELECT rep_id_string FROM course_representative WHERE repID = :rid LIMIT 1");
        $repStmt->execute([':rid' => $repID]);
        $rep_id_string = $repStmt->fetchColumn();
        if (!$rep_id_string) return;

        // Extract course code and batch year from rep_id_string
        $parts = explode('/', strtoupper(trim($rep_id_string)));
        if (count($parts) < 3) return;
        $courseCode = $parts[1]; // CST
        $batchYear = (int)$parts[2]; // 23

        $message = "Students in Batch {$batchYear} have requested Peer Learning for \"{$unitName}\". If you can help, please reach out to the Course Rep!";

        // Find all students in this course with batch year <= rep's batch year
        $pattern = '%/' . strtolower($courseCode) . '/%';
        $students = $db->prepare(
            "SELECT s.enrollmentNo 
             FROM student s
             JOIN users u ON s.userID = u.userID
             WHERE LOWER(s.enrollmentNo) LIKE :pat
             AND u.peer_learning_app_notification = 1"
        );
        $students->execute([':pat' => $pattern]);

        $ins = $db->prepare("INSERT INTO app_notification (repID, enrollmentNo, message) VALUES (:rid, :enr, :msg)");
        while ($row = $students->fetch(\PDO::FETCH_ASSOC)) {
            $studentBatch = $this->getBatchYear($row['enrollmentNo']);
            if ($studentBatch !== null && $studentBatch <= $batchYear) {
                // Insert notification
                $ins->execute([':rid' => $repID, ':enr' => $row['enrollmentNo'], ':msg' => $message]);
            }
        }
    }
}
