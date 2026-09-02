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
            $appNotifDao = new \DAO\AppNotificationDAO();
            $notifications = $appNotifDao->getRecentForUser($userID, 5);
            
            foreach ($notifications as $notif) {
                $activities[] = [
                    'id' => uniqid(),
                    'type' => 'notification',
                    'title' => 'New Notification',
                    'description' => $notif['message'],
                    'created_at' => $notif['created_at'],
                    'link' => '#'
                ];
            }
            
            if ($decoded->role !== 'staff') {
                $notesDao = new \DAO\NotesDAO();
                $recentNotes = $notesDao->getRecent(5);
                
                foreach ($recentNotes as $n) {
                    $activities[] = [
                        'id' => uniqid(),
                        'type' => 'note',
                        'title' => 'New Note Uploaded',
                        'description' => ($n['title'] ?: 'Course Material') . ' for ' . $n['courseUnitID'],
                        'created_at' => $n['created_at'],
                        'link' => '/notes'
                    ];
                }
            }

            // Fetch lost items reported by user
            $lostItemDao = new \DAO\LostItemDAO();
            $recentLost = $lostItemDao->getRecentByUser($userID, 5);
            foreach ($recentLost as $l) {
                $activities[] = [
                    'id' => uniqid(),
                    'type' => 'lost_item',
                    'title' => 'Lost Item Reported',
                    'description' => 'You reported: ' . $l['lostItemName'],
                    'created_at' => $l['created_at'],
                    'link' => '/lost-items'
                ];
            }

            // Fetch marketplace products listed by user
            $marketplaceDao = new \DAO\MarketplaceDAO();
            $recentMarket = $marketplaceDao->getRecentByUser($userID, 5);
            foreach ($recentMarket as $m) {
                $activities[] = [
                    'id' => uniqid(),
                    'type' => 'marketplace',
                    'title' => 'Product Listed',
                    'description' => 'You listed: ' . $m['productName'] . ' - Rs.' . $m['price'],
                    'created_at' => $m['created_at'],
                    'link' => '/marketplace'
                ];
            }

            // Sort mixed activities by timestamp descending
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // Limit total to 5
            $activities = array_slice($activities, 0, 5);
        
            if (empty($activities)) {
                $activities[] = [
                    'id' => uniqid(),
                    'type' => 'system',
                    'title' => 'Welcome to UniCore',
                    'description' => 'Your recent activities will appear here once you start exploring the platform.',
                    'created_at' => date('Y-m-d H:i:s'),
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
