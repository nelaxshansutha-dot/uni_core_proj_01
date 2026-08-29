<?php
namespace Controllers;
use Middleware\AuthMiddleware;
use Config\Database;

class NotificationController {

    /**
     * GET /notifications  — used by the Topbar bell for ALL roles
     * Only returns peer_learning (app_notification) rows — lost-item alerts are NOT shown here.
     */
    public function getNotifications() {
        $decoded = AuthMiddleware::authenticate();
        $userID  = $decoded->userID;
        $db      = Database::getInstance()->getConnection();

        try {
            // Use UNION so this works for both regular students and course representatives.
            // Students: look up via the student table.
            // Reps: look up via the course_representative table (which also holds an enrollmentNo).
            $stmt = $db->prepare(
                "SELECT an.appID AS id, an.message, an.created_at, 'peer_learning' AS type
                 FROM app_notification an
                 WHERE an.enrollmentNo IN (
                     SELECT s.enrollmentNo FROM student s WHERE s.userID = :uid1
                     UNION
                     SELECT cr.enrollmentNo FROM course_representative cr WHERE cr.userID = :uid2
                 )
                 ORDER BY an.created_at DESC
                 LIMIT 20"
            );
            $stmt->execute([':uid1' => $userID, ':uid2' => $userID]);
            $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'success' => true,
                'data'   => $notifications
            ]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch notifications']);
        }
    }

    /**
     * GET /notifications/app — used by the Notifications page (student/rep only)
     * Reads from legacy app_notification table (tied to enrollmentNo).
     */
    public function handleApp($method, $id = null) {
        $decoded = AuthMiddleware::authenticate(['student', 'course_representative']);
        $enrollmentNo = $decoded->enrollmentNo ?? null;
        $db = Database::getInstance()->getConnection();

        if ($method === 'DELETE') {
            if (!$this->validateNotificationID($id)) {
                return;
            }

            // Dismiss a specific notification
            $stmt = $db->prepare("DELETE FROM app_notification WHERE appID = :id AND enrollmentNo = :enr");
            $stmt->execute([':id' => $id, ':enr' => $enrollmentNo]);
            echo json_encode(['status' => 'success']);
            return;
        }

        // GET all
        try {
            if (!$enrollmentNo) {
                echo json_encode(['status' => 'success', 'data' => []]);
                return;
            }
            $stmt = $db->prepare(
                "SELECT appID, message, created_at
                 FROM app_notification
                 WHERE enrollmentNo = :enr
                 ORDER BY created_at DESC LIMIT 20"
            );
            $stmt->execute([':enr' => $enrollmentNo]);
            $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'success' => true,
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch notifications']);
        }
    }

    /**
     * DELETE /notifications/{id}  — dismiss a peer-learning bell notification
     */
    public function dismiss($id) {
        $decoded      = AuthMiddleware::authenticate();
        $userID       = $decoded->userID;
        $enrollmentNo = $decoded->enrollmentNo ?? null;
        $db           = Database::getInstance()->getConnection();

        if (!$this->validateNotificationID($id)) {
            return;
        }

        // For course_representative users the JWT may not carry enrollmentNo,
        // so resolve it from the course_representative table if needed.
        if (!$enrollmentNo && ($decoded->role ?? '') === 'course_representative') {
            $repStmt = $db->prepare("SELECT enrollmentNo FROM course_representative WHERE userID = :uid LIMIT 1");
            $repStmt->execute([':uid' => $userID]);
            $repRow = $repStmt->fetch(\PDO::FETCH_ASSOC);
            $enrollmentNo = $repRow['enrollmentNo'] ?? null;
        }

        // Only dismiss from app_notification (peer_learning); lost-item SMS rows are unaffected
        if ($enrollmentNo) {
            $stmt = $db->prepare("DELETE FROM app_notification WHERE appID = :id AND enrollmentNo = :enr");
            $stmt->execute([':id' => $id, ':enr' => $enrollmentNo]);
        }

        echo json_encode(['status' => 'success']);
    }

    private function validateNotificationID($id): bool {
        $validator = new \Utils\Validator(['notification_id' => $id]);
        $validator->validate(['notification_id' => 'required|positiveInteger']);

        if ($validator->passes()) {
            return true;
        }

        echo json_encode([
            'status' => 'error',
            'message' => $validator->getFirstError(),
            'errors' => $validator->getErrors()
        ]);
        return false;
    }
}
