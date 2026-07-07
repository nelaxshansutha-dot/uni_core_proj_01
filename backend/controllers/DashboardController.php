<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/Response.php';

require_once __DIR__ . '/BaseController.php';

class DashboardController extends BaseController {
    
    public function getRecentActivity($user) {
        $activities = [];

        // 1. Fetch latest Lost Items (max 5)
        require_once __DIR__ . '/../models/LostItem.php';
        $lostItemModel = new LostItem();
        $lostItems = $lostItemModel->getLatestItems(5);
        foreach ($lostItems as $row) {
            $row['description'] = "A lost item '" . $row['title'] . "' was reported.";
            $row['link'] = "/lost-items";
            $activities[] = $row;
        }

        // 2. Fetch latest Marketplace products (max 5)
        require_once __DIR__ . '/../models/Marketplace.php';
        $marketplaceModel = new Marketplace();
        $marketplaceItems = $marketplaceModel->getLatestItems(5);
        foreach ($marketplaceItems as $row) {
            $row['description'] = "New product '" . $row['title'] . "' added to marketplace.";
            $row['link'] = "/marketplace";
            $activities[] = $row;
        }

        // 3. Fetch latest Notes (if user is student/rep)
        if ($user['role'] === 'student' || $user['role'] === 'rep') {
            require_once __DIR__ . '/../models/Note.php';
            $noteModel = new Note();
            $notes = $noteModel->getLatestNotes(5);
            foreach ($notes as $row) {
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
