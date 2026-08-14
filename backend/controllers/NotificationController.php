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
            $stmt = $db->prepare(
                "SELECT an.appID AS id, an.message, an.created_at, 'peer_learning' AS type
                 FROM app_notification an
                 JOIN student s ON an.enrollmentNo = s.enrollmentNo
                 WHERE s.userID = :uid
                 ORDER BY an.created_at DESC
                 LIMIT 20"
            );
            $stmt->execute([':uid' => $userID]);
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

        if ($method === 'DELETE' && $id) {
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
        $enrollmentNo = $decoded->enrollmentNo ?? null;
        $db           = Database::getInstance()->getConnection();

        // Only dismiss from app_notification (peer_learning); lost-item SMS rows are unaffected
        if ($enrollmentNo) {
            $stmt = $db->prepare("DELETE FROM app_notification WHERE appID = :id AND enrollmentNo = :enr");
            $stmt->execute([':id' => $id, ':enr' => $enrollmentNo]);
        }

        echo json_encode(['status' => 'success']);
    }
}
