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
                "SELECT sn.smsID AS id, sn.message, sn.created_at,
                        li.lostItemName
                 FROM sms_notification sn
                 LEFT JOIN lost_items li ON sn.lostID = li.lostID
                 WHERE sn.userID = :uid
                 ORDER BY sn.created_at DESC
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
     * DELETE /notifications/{id}  — dismiss a lost-item notification from the bell
     */
    public function dismiss($id) {
        $decoded = AuthMiddleware::authenticate();
        $userID  = $decoded->userID;
        $db      = Database::getInstance()->getConnection();

        $stmt = $db->prepare("DELETE FROM sms_notification WHERE smsID = :id AND userID = :uid");
        $stmt->execute([':id' => $id, ':uid' => $userID]);
        echo json_encode(['status' => 'success']);
    }
}
