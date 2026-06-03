<?php
require_once __DIR__ . '/../models/LostItem.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class LostItemController {
    
    public function getItems() {
        $model = new LostItem();
        $items = $model->getAll();
        Response::success("Lost and found items retrieved", $items);
    }

    public function createItem($data, $user_id) {
        $missing = Validator::required(['title', 'description'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        $model = new LostItem();
        $itemData = [
            'user_id' => $user_id,
            'title' => $data['title'],
            'description' => $data['description'],
            'image_url' => isset($data['image_url']) ? $data['image_url'] : null,
            'status' => 'lost'
        ];

        if ($model->create($itemData)) {
            Response::success("Item reported successfully.");
        } else {
            Response::error("Failed to report item.", 500);
        }
    }

    public function updateStatus($data, $user_id) {
        $missing = Validator::required(['id', 'status'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields.");
        }

        $model = new LostItem();
        if ($model->updateStatus($data['id'], $user_id, $data['status'])) {
            Response::success("Status updated.");
        } else {
            Response::error("Failed to update status. You may not own this item.", 403);
        }
    }
}
?>
