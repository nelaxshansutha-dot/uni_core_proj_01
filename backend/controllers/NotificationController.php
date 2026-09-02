<?php
namespace Controllers;
use Middleware\AuthMiddleware;
use Config\Database;

class NotificationController {

   
    public function getNotifications() {
        $decoded = AuthMiddleware::authenticate();
        $userID  = $decoded->userID;
        $db      = Database::getInstance()->getConnection();

        try {
            $appNotifDao = new \DAO\AppNotificationDAO();
            $notifications = $appNotifDao->getNotificationsForUser($userID);

            echo json_encode([
                'status' => 'success',
                'success' => true,
                'data'   => $notifications
            ]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch notifications']);
        }
    }


    public function handleApp($method, $id = null) {
        $decoded = AuthMiddleware::authenticate(['student', 'course_representative']);
        $enrollmentNo = $decoded->enrollmentNo ?? null;
        $db = Database::getInstance()->getConnection();

        if ($method === 'DELETE') {
            if (!$this->validateNotificationID($id)) {
                return;
            }

            // Dismiss a specific notification
            $appNotifDao = new \DAO\AppNotificationDAO();
            $appNotifDao->deleteByAppIDAndEnrollment($id, $enrollmentNo);
            echo json_encode(['status' => 'success']);
            return;
        }

        // GET all
        try {
            if (!$enrollmentNo) {
                echo json_encode(['status' => 'success', 'data' => []]);
                return;
            }
            $appNotifDao = new \DAO\AppNotificationDAO();
            $notifications = $appNotifDao->getAllByEnrollment($enrollmentNo);

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
            $courseRepDao = new \DAO\CourseRepresentativeDAO();
            $enrollmentNo = $courseRepDao->getEnrollmentNoByUserId($userID);
        }

        // Only dismiss from app_notification (peer_learning); lost-item SMS rows are unaffected
        if ($enrollmentNo) {
            $appNotifDao = new \DAO\AppNotificationDAO();
            $appNotifDao->deleteByAppIDAndEnrollment($id, $enrollmentNo);
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
