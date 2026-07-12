<?php
namespace Controllers;
use Middleware\AuthMiddleware;
use Config\Database;

class DashboardController {
    public function getRecentActivity() {
        $decoded = AuthMiddleware::authenticate();
        $userID = $decoded->userID;
        
        $db = Database::getInstance()->getConnection();
        $activities = [];

        // Fetch recent app notifications
        try {
            $stmt = $db->prepare("SELECT message, created_at FROM app_notification an JOIN student s ON an.enrollmentNo = s.enrollmentNo WHERE s.userID = :uid ORDER BY an.created_at DESC LIMIT 5");
            $stmt->execute([':uid' => $userID]);
            $notifications = $stmt->fetchAll();
            
            foreach ($notifications as $notif) {
                $activities[] = [
                    'id' => uniqid(),
                    'type' => 'notification',
                    'title' => 'New Notification',
                    'description' => $notif['message'],
                    'timestamp' => $notif['created_at'],
                    'link' => '#'
                ];
            }
            
            // If no activities found, provide a default welcome message
            if (empty($activities)) {
                $activities[] = [
                    'id' => uniqid(),
                    'type' => 'system',
                    'title' => 'Welcome to UniCore',
                    'description' => 'Your recent activities will appear here once you start exploring the platform.',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'link' => '#'
                ];
            }
            
        } catch (\Exception $e) {
            // Silently fail and return empty if error
        }

        echo json_encode([
            'status' => 'success',
            'data' => [
                'activities' => $activities
            ]
        ]);
    }
}
