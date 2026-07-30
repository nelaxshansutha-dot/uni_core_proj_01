<?php
namespace Controllers;
use Models\LostItem;
use Middleware\AuthMiddleware;
use PDO;

class LostItemController {
    public function handleRequest($method, $id = null) {
        $decoded = AuthMiddleware::authenticate();
        $model = new LostItem();

        if ($method === 'GET') {
            echo json_encode(['success' => true, 'data' => $model->view($id)]);
        } elseif ($method === 'POST') {
            
            $data = $_POST;
            $data['userID'] = $decoded->userID;
            
            if (isset($_FILES['item_image'])) {
                if ($_FILES['item_image']['error'] !== UPLOAD_ERR_OK) {
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
                    ];
                    $errorMsg = $uploadErrors[$_FILES['item_image']['error']] ?? 'Unknown upload error';
                    echo json_encode(['success' => false, 'message' => 'Upload failed: ' . $errorMsg]);
                    return;
                }
                
                $mimeType = mime_content_type($_FILES['item_image']['tmp_name']);
                $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                
                if (!in_array($mimeType, $allowedImageTypes)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and WebP are allowed.']);
                    return;
                }

                try {
                    $url = \Services\CloudinaryUploader::upload($_FILES['item_image']['tmp_name'], 'image');
                    $data['item_image'] = $url;
                } catch (\Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Cloudinary Upload Failed: ' . $e->getMessage()]);
                    return;
                }
            }
            
            
            if (isset($data['update_id'])) {
                $model = new LostItem($data);
                $success = $model->update($data['update_id']);
                echo json_encode(['success' => $success]);
            } else {
                $model = new LostItem($data);
                $lostID = $model->create();
                
                // Trigger SMS Broadcast if requested
                if (isset($data['send_sms_alert']) && ($data['send_sms_alert'] === 'true' || $data['send_sms_alert'] === true || $data['send_sms_alert'] === '1')) {
                    try {
                        $db = \Config\Database::getInstance()->getConnection();
                        // Query users who have opted in (assuming lost_item_sms_notification = 1) and have a valid contactNumber
                        $stmt = $db->query("SELECT contactNumber FROM users WHERE contactNumber IS NOT NULL AND contactNumber != '' AND lost_item_sms_notification = 1");
                        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        $itemName = $data['lostItemName'] ?? 'An item';
                        $message = "A new lost item was just reported: $itemName. Check the UniCore portal for details!";
                        
                        foreach ($users as $phone) {
                            \Utils\SMSService::sendSMS($phone, $message);
                        }
                    } catch (\Exception $e) {
                        error_log("[UniCore SMS] Broadcast failed: " . $e->getMessage());
                    }
                }
                
                echo json_encode(['success' => true, 'lostID' => $lostID]);
            }
            
        } elseif ($method === 'PUT') {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) $data = $_POST; // Fallback
            
            // Handle User SMS Preference update
            if (isset($data['update_preference'])) {
                $db = \Config\Database::getInstance()->getConnection();
                
                // Ensure columns exist (creates them if they don't, to prevent DB errors during demo)
                try {
                    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS lost_item_sms_notification TINYINT(1) DEFAULT 0");
                    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS has_seen_lost_item_popup TINYINT(1) DEFAULT 0");
                } catch (\Exception $e) {}
                
                $stmt = $db->prepare("UPDATE users SET lost_item_sms_notification = :sms, has_seen_lost_item_popup = :popup WHERE userID = :uid");
                $success = $stmt->execute([
                    ':sms' => $data['lost_item_sms_notification'] ?? 0,
                    ':popup' => $data['has_seen_lost_item_popup'] ?? 1,
                    ':uid' => $decoded->userID
                ]);
                echo json_encode(['success' => $success]);
                return;
            }
            
            $model = new LostItem($data);
            $success = $model->update($id);
            echo json_encode(['success' => $success]);
            
        } elseif ($method === 'DELETE') {
            $success = $model->delete($id, $decoded->userID);
            echo json_encode(['success' => $success]);
        }
    }
}
