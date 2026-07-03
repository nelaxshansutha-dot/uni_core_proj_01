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
        $missing = Validator::required(['item_name', 'description', 'price', 'condition_type', 'location', 'phone_number'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
            return;
        }

        if (!preg_match('/^[0-9]+$/', $data['phone_number'])) {
            Response::error("Phone number must contain only numbers.");
            return;
        }

        // Used condition requires usage_duration
        if ($data['condition_type'] === 'used' && empty($data['usage_duration'])) {
            Response::error("Please specify how long you have used this item.");
        }

        $model = new Marketplace();
        $itemData = [
            'userID'         => $user_id,
            'item_name'      => $data['item_name'],
            'description'    => $data['description'],
            'price'          => $data['price'],
            'condition_type' => $data['condition_type'],
            'location'       => $data['location'],
            'phone_number'   => $data['phone_number'],
            'usage_duration' => isset($data['usage_duration']) ? $data['usage_duration'] : null,
            'image_url'      => isset($data['image_url'])  ? $data['image_url']  : null,
            'image_url2'     => isset($data['image_url2']) ? $data['image_url2'] : null,
            'image_url3'     => isset($data['image_url3']) ? $data['image_url3'] : null,
            'image_url4'     => isset($data['image_url4']) ? $data['image_url4'] : null,
            'status'         => 'available'
        ];

        if ($model->create($itemData)) {
            Response::success("Item listed successfully.");
        } else {
            Response::error("Failed to list item.", 500);
        }
    }

    public function updateStatus($data, $user_id) {
        $missing = Validator::required(['productID', 'status'], $data);
        if (!empty($missing)) {
             Response::error("Missing fields.");
        }

        $model = new Marketplace();
        if ($model->updateStatus($data['productID'], $user_id, $data['status'])) {
            Response::success("Status updated.");
        } else {
            Response::error("Failed to update status. You may not own this item.", 403);
        }
    }

    public function updateListing($data, $user_id) {
        $missing = Validator::required(['productID', 'item_name', 'description', 'price', 'condition_type', 'location', 'phone_number'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
            return;
        }

        if (!preg_match('/^[0-9]+$/', $data['phone_number'])) {
            Response::error("Phone number must contain only numbers.");
            return;
        }

        if ($data['condition_type'] === 'used' && empty($data['usage_duration'])) {
            Response::error("Please specify how long you have used this item.");
        }

        $model = new Marketplace();
        $itemData = [
            'productID'      => $data['productID'],
            'item_name'      => $data['item_name'],
            'description'    => $data['description'],
            'price'          => $data['price'],
            'condition_type' => $data['condition_type'],
            'location'       => $data['location'],
            'phone_number'   => $data['phone_number'],
            'usage_duration' => isset($data['usage_duration']) ? $data['usage_duration'] : null,
            'image_url'      => isset($data['image_url'])  ? $data['image_url']  : null,
            'image_url2'     => isset($data['image_url2']) ? $data['image_url2'] : null,
            'image_url3'     => isset($data['image_url3']) ? $data['image_url3'] : null,
            'image_url4'     => isset($data['image_url4']) ? $data['image_url4'] : null,
        ];

        if ($model->update($itemData, $user_id)) {
            Response::success("Item updated successfully.");
        } else {
            Response::error("Failed to update item. You may not own this item.", 403);
        }
    }

    public function deleteItem($data, $user_id) {
        $missing = Validator::required(['productID'], $data);
        if (!empty($missing)) {
            Response::error("Missing item ID.");
        }

        $model = new Marketplace();
        if ($model->delete($data['productID'], $user_id)) {
            Response::success("Item deleted successfully.");
        } else {
            Response::error("Failed to delete item. You may not own this item.", 403);
        }
    }
}
?>
