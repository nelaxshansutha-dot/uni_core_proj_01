<?php
namespace Controllers;
use Models\PeerLearningRequest;
use Middleware\AuthMiddleware;

class PeerLearningRequestController {
    public function handleRequest($method, $id = null, $action = null) {
        $decoded = AuthMiddleware::authenticate(['student', 'course_representative']);
        $model = new PeerLearningRequest();

        if ($method === 'GET') {
            echo json_encode(['success' => true, 'data' => $model->view($id)]);
        } elseif ($method === 'POST' || $method === 'PUT') {
            $data = json_decode(file_get_contents("php://input"), true);
            
            // If the rep is reviewing/updating the status (e.g., Accepting the request)
            if (isset($data['status']) && $id) {
                // Only rep can review
                AuthMiddleware::authenticate(['course_representative']);
                $success = $model->review($id, $data['status']);
                
                // If it was accepted, dispatch notifications to the batch and senior batches
                if ($success && $data['status'] === 'accepted') {
                    $this->dispatchBatchNotifications($decoded, $data);
                }
                
                echo json_encode(['success' => $success]);
                return;
            }

            // Normal student submission
            $data['enrollmentNo'] = $decoded->enrollmentNo;
            echo json_encode(['success' => $model->submit($data)]);
        }
    }

    private function dispatchBatchNotifications($decodedUser, $requestData) {
        $db = \Config\Database::getInstance()->getConnection();
        
        // Example Rep Enrollment: uwu/cst/23/088
        $repEnrollment = $decodedUser->enrollmentNo;
        
        // Parse the enrollment number to get course and year
        $parts = explode('/', $repEnrollment);
        if (count($parts) >= 3) {
            $course = strtolower($parts[1]); // e.g., 'cst'
            $repYear = (int)$parts[2]; // e.g., 23
            
            // Notification Message
            $courseUnitName = $requestData['courseUnitName'] ?? 'a module';
            $message = "A peer learning session for {$courseUnitName} has been scheduled by your Course Representative.";
            $repID = method_exists($decodedUser, 'getRepID') ? $decodedUser->getRepID() : null;
            
            // Query for students in the same course who are in the same batch OR senior batches (year <= repYear)
            $query = "SELECT enrollmentNo FROM student WHERE LOWER(enrollmentNo) LIKE :coursePattern";
            $stmt = $db->prepare($query);
            $stmt->execute([':coursePattern' => "%/{$course}/%"]);
            
            $targetStudents = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $studentEnr = $row['enrollmentNo'];
                $sParts = explode('/', $studentEnr);
                if (count($sParts) >= 3) {
                    $sYear = (int)$sParts[2];
                    // Include same year and senior years (smaller number)
                    if ($sYear <= $repYear) {
                        $targetStudents[] = $studentEnr;
                    }
                }
            }
            
            // Insert notification for each target student
            if (!empty($targetStudents)) {
                $insertQuery = "INSERT INTO app_notification (repID, enrollmentNo, message) VALUES (:rid, :enr, :msg)";
                $insertStmt = $db->prepare($insertQuery);
                
                foreach ($targetStudents as $enr) {
                    $insertStmt->execute([
                        ':rid' => $repID,
                        ':enr' => $enr,
                        ':msg' => $message
                    ]);
                }
            }
        }
    }
}
