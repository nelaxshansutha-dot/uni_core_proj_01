<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/Response.php';

class DashboardController {
    
    public function getRecentActivity($user) {
        $db = (new Database())->getConnection();
        $activities = [];

        // 1. Fetch latest Lost Items (max 5)
        $stmt = $db->prepare("
            SELECT lostID as id, item_name as title, 'lost_item' as type, created_at
            FROM Lost_items
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['description'] = "A lost item '" . $row['title'] . "' was reported.";
            $row['link'] = "/lost-items";
            $activities[] = $row;
        }

        // 2. Fetch latest Marketplace products (max 5)
        $stmt = $db->prepare("
            SELECT productID as id, product_name as title, 'marketplace' as type, created_at
            FROM marketplace
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['description'] = "New product '" . $row['title'] . "' added to marketplace.";
            $row['link'] = "/marketplace";
            $activities[] = $row;
        }

        // 3. Fetch latest Notes (if user is student/rep)
        if ($user['role'] === 'student' || $user['role'] === 'rep') {
            $stmt = $db->prepare("
                SELECT noteID as id, title, 'note' as type, created_at
                FROM Notes
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['description'] = "New note '" . $row['title'] . "' was uploaded.";
                $row['link'] = "/notes";
                $activities[] = $row;
            }
        }

        // Sort combined activities by created_at descending
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Return top 10 recent activities
        $recentActivities = array_slice($activities, 0, 10);

        Response::success("Recent activity fetched successfully.", [
            'activities' => $recentActivities
        ]);
    }
}
?>
