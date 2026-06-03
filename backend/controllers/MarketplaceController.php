<?php
require_once __DIR__ . '/../models/Marketplace.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class MarketplaceController {
    
    public function getItems() {
        $model = new Marketplace();
        $items = $model->getAll();
        Response::success("Marketplace items retrieved", $items);
    }

    public function createItem($data, $user_id) {
        $missing = Validator::required(['item_name', 'description', 'price'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        $model = new Marketplace();
        $itemData = [
            'seller_id' => $user_id,
            'item_name' => $data['item_name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'image_url' => isset($data['image_url']) ? $data['image_url'] : null,
            'status' => 'available'
        ];

        if ($model->create($itemData)) {
            Response::success("Item listed successfully.");
        } else {
            Response::error("Failed to list item.", 500);
        }
    }

    public function updateStatus($data, $user_id) {
        $missing = Validator::required(['id', 'status'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields.");
        }

        $model = new Marketplace();
        if ($model->updateStatus($data['id'], $user_id, $data['status'])) {
            Response::success("Status updated.");
        } else {
            Response::error("Failed to update status. You may not own this item.", 403);
        }
    }
}
?>
