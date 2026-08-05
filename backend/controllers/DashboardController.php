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
            
            if ($decoded->role !== 'staff') {
                $stmtNotes = $db->prepare("SELECT title, courseUnitID, created_at FROM notes ORDER BY created_at DESC LIMIT 5");
                $stmtNotes->execute();
                $recentNotes = $stmtNotes->fetchAll();
                
                foreach ($recentNotes as $n) {
                    $activities[] = [
                        'id' => uniqid(),
                        'type' => 'note',
                        'title' => 'New Note Uploaded',
                        'description' => ($n['title'] ?: 'Course Material') . ' for ' . $n['courseUnitID'],
                        'timestamp' => $n['created_at'],
                        'link' => '/notes'
                    ];
                }
            }

            // Sort mixed activities by timestamp descending
            usort($activities, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });
            
            // Limit total to 5
            $activities = array_slice($activities, 0, 5);
        
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
