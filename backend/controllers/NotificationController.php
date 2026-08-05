<?php
namespace Controllers;
use Middleware\AuthMiddleware;
use Config\Database;

class NotificationController {

    /**
     * GET /notifications  — used by the Topbar bell for ALL roles
     * Reads from sms_notification (keyed by userID) which stores lost-item alerts.
     */
    public function getNotifications() {
        $decoded = AuthMiddleware::authenticate();
        $userID  = $decoded->userID;
        $db      = Database::getInstance()->getConnection();

        try {
            $stmt = $db->prepare(
                "(SELECT sn.smsID AS id, sn.message, sn.created_at, 'lost_item' AS type
                  FROM sms_notification sn
                  WHERE sn.userID = :uid)
                 UNION ALL
                 (SELECT an.appID AS id, an.message, an.created_at, 'peer_learning' AS type
                  FROM app_notification an
                  JOIN student s ON an.enrollmentNo = s.enrollmentNo
                  WHERE s.userID = :uid2)
                 ORDER BY created_at DESC
                 LIMIT 20"
            );
            $stmt->execute([':uid' => $userID, ':uid2' => $userID]);
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
     * DELETE /notifications/{id}  — dismiss a notification from the bell
     */
    public function dismiss($id) {
        $decoded = AuthMiddleware::authenticate();
        $userID  = $decoded->userID;
        $db      = Database::getInstance()->getConnection();

        // Dismiss from sms_notification
        $stmt = $db->prepare("DELETE FROM sms_notification WHERE smsID = :id AND userID = :uid");
        $stmt->execute([':id' => $id, ':uid' => $userID]);

        // Dismiss from app_notification
        $enrollmentNo = $decoded->enrollmentNo ?? null;
        if ($enrollmentNo) {
            $stmt2 = $db->prepare("DELETE FROM app_notification WHERE appID = :id AND enrollmentNo = :enr");
            $stmt2->execute([':id' => $id, ':enr' => $enrollmentNo]);
        }

        echo json_encode(['status' => 'success']);
    }
}
