<?php
namespace Controllers;
use Middleware\AuthMiddleware;
use Config\Database;

class NotificationController {
    public function getNotifications() {
        $decoded = AuthMiddleware::authenticate(['student', 'course_representative']);
        $enrollmentNo = $decoded->enrollmentNo;
        
        $db = Database::getInstance()->getConnection();
        
        try {
            // Fetch notifications for this student
            $stmt = $db->prepare("SELECT message, created_at FROM app_notification WHERE enrollmentNo = :enr ORDER BY created_at DESC LIMIT 20");
            $stmt->execute([':enr' => $enrollmentNo]);
            $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to fetch notifications'
            ]);
        }
    }
}
