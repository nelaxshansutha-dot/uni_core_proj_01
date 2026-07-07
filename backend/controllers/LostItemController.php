
<?php
require_once __DIR__ . '/../models/LostItem.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/SMSService.php';

require_once __DIR__ . '/BaseController.php';

class LostItemController extends BaseController {

    public function getItems() {
        $model = new LostItem();
        $items = $model->getAll();
        Response::success("Lost and found items retrieved", $items);
    }

    public function createItem($data, $file, $user_id) {

        Validator::validateRequired([
            'lostItemName',
            'description',
            'last_seen_datetime',
            'last_seen_place',
            'contact_number'
        ], $data);

        if (!preg_match('/^[0-9]+$/', $data['contact_number'])) {
            Response::error("Contact number must contain only numbers.");
            return;
        }

        if (strtotime($data['last_seen_datetime']) > time()) {
            Response::error("Last seen date cannot be in the future.");
            return;
        }

        // ---------------- IMAGE UPLOAD ----------------
        $imagePath = null;

        if ($file && isset($file['error']) && $file['error'] === 0) {

            $uploadDir = __DIR__ . "/../uploads/lost_items/";

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . "_" . basename($file['name']);
            $targetFile = $uploadDir . $fileName;

            move_uploaded_file($file['tmp_name'], $targetFile);

            $imagePath = "uploads/lost_items/" . $fileName;
        }

        // ---------------- DATA ----------------
        $itemData = [
            'user_id' => $user_id,
            'lostItemName' => $data['lostItemName'],
            'description' => $data['description'],
            'last_seen_datetime' => $data['last_seen_datetime'],
            'last_seen_place' => $data['last_seen_place'],
            'contact_number' => $data['contact_number'],
            'item_image' => $imagePath
        ];

        $model = new LostItem();

        // ---------------- INSERT ----------------
        if ($model->create($itemData)) {
            // Trigger SMS notifications to ALL registered users who have a phone number
            try {
                $db = (new Database())->getConnection();
                $stmt = $db->prepare("SELECT phoneNum FROM Users WHERE phoneNum IS NOT NULL AND phoneNum != '' AND lost_item_sms_notification = 1");
                $stmt->execute();
                $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($allUsers)) {
                    $smsMessage = "UniCore Alert: A new lost item '" . $data['lostItemName'] . "' has been reported at " . $data['last_seen_place'] . ". Contact: " . $data['contact_number'];
                    foreach ($allUsers as $user) {
                        SMSService::sendSMS($user['phoneNum'], $smsMessage);
                    }
                }
            } catch (Exception $e) {
                // Fail silently so item reporting completes even if SMS gateway encounters issues
            }

            Response::success("Item reported successfully.");
        } else {
            Response::error("Failed to report the item.", 500);
        }
    }


    public function updateItem($data, $file, $user_id) {
        Validator::validateRequired([
            'update_id',
            'lostItemName',
            'description',
            'last_seen_datetime',
            'last_seen_place',
            'contact_number'
        ], $data);

        if (!preg_match('/^[0-9]+$/', $data['contact_number'])) {
            Response::error("Contact number must contain only numbers.");
            return;
        }

        if (strtotime($data['last_seen_datetime']) > time()) {
            Response::error("Last seen date cannot be in the future.");
            return;
        }

        // ---------------- IMAGE UPLOAD ----------------
        $imagePath = null;

        if ($file && isset($file['error']) && $file['error'] === 0) {
            $uploadDir = __DIR__ . "/../uploads/lost_items/";

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . "_" . basename($file['name']);
            $targetFile = $uploadDir . $fileName;

            move_uploaded_file($file['tmp_name'], $targetFile);

            $imagePath = "uploads/lost_items/" . $fileName;
        }

        // ---------------- DATA ----------------
        $itemData = [
            'lost_id' => $data['update_id'],
            'user_id' => $user_id,
            'lostItemName' => $data['lostItemName'],
            'description' => $data['description'],
            'last_seen_datetime' => $data['last_seen_datetime'],
            'last_seen_place' => $data['last_seen_place'],
            'contact_number' => $data['contact_number'],
            'item_image' => $imagePath
        ];

        $model = new LostItem();

        // ---------------- UPDATE ----------------
        if ($model->update($itemData)) {
            Response::success("Item updated successfully.");
        } else {
            Response::error("Failed to update the item.", 500);
        }
    }

    public function deleteItem($itemId, $userId) {

        $model = new LostItem();
        if ($model->delete($itemId, $userId)) {
            Response::success("Item deleted successfully.");
        } else {
            Response::error("Failed to delete the item. You may not be authorized.", 403);
        }
    }
}
?>


