<?php
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../utils/Response.php';

require_once __DIR__ . '/BaseController.php';

class NotificationController extends BaseController {
    
    public function getUserNotifications($user_id) {
        $model = new Notification();
        $notifications = $model->getUserNotifications($user_id);
        Response::success("Notifications retrieved", $notifications);
    }

    public function markAsRead($data, $user_id) {
        if (!isset($data['recipient_id'])) {
            Response::error("Missing recipient_id.");
        }

        $model = new Notification();
        if ($model->markAsRead($data['recipient_id'], $user_id)) {
            Response::success("Marked as read.");
        } else {
            Response::error("Failed to mark as read.", 500);
        }
    }
}
?>
