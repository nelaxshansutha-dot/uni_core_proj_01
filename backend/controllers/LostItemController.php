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
            if ($id !== null && !$this->validatePayload(['lostID' => $id], ['lostID' => 'required|positiveInteger'])) {
                return;
            }
            echo json_encode(['success' => true, 'data' => $model->view($id)]);
        } elseif ($method === 'POST') {
            
            $data = $_POST;
            
            if (!$this->validatePayload($data, [
                'lostItemName' => 'required|string|maxLength:150',
                'last_seen_place' => 'required|string|maxLength:150',
                'last_seen_datetime' => 'required|string|dateFormat:Y-m-d\TH:i|beforeOrEqual:now',
                'contact_number' => 'required|phone',
                'description' => 'required|string|maxLength:1000',
                'send_sms_alert' => 'nullable|boolean',
                'update_id' => 'nullable|positiveInteger'
            ])) {
                return;
            }

            if (!$this->validatePayload(
                ['item_image' => $_FILES['item_image'] ?? null],
                ['item_image' => 'nullable|maxFileSize:5242880|mimes:image/jpeg,image/png,image/webp']
            )) {
                return;
            }
            
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
                    // Log the error but continue saving without the image
                    error_log('[UniCore] Cloudinary upload failed: ' . $e->getMessage());
                    $data['item_image'] = null;
                }
            }
            
            
            if (isset($data['update_id'])) {
                $model = new LostItem();
                $model->hydrateFromRequest($data);
                $model->setLostID($data['update_id']);
                $model->setUserID($decoded->userID);
                $success = $model->update();
                echo json_encode(['success' => $success]);
            } else {
                $model = new LostItem();
                $model->hydrateFromRequest($data);
                $model->setUserID($decoded->userID);
                $lostID = $model->create();
                
                $db = \Config\Database::getInstance()->getConnection();
                $itemName = $data['lostItemName'] ?? 'An item';

                // ---- IN-APP NOTIFICATION for ALL active users ----
                try {
                    $notifMessage = "A new lost item was reported: \"$itemName\". Check Lost-Items for details.";
                    // Fetch all active users except the one who posted
                    $stmtUsers = $db->prepare("SELECT userID FROM users WHERE is_active = 1 AND userID != :uid");
                    $stmtUsers->execute([':uid' => $decoded->userID]);
                    $allUsers = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);

                    $stmtNotif = $db->prepare("INSERT INTO sms_notification (lostID, userID, message) VALUES (:lid, :uid, :msg)");
                    foreach ($allUsers as $uid) {
                        $stmtNotif->execute([':lid' => $lostID, ':uid' => $uid, ':msg' => $notifMessage]);
                    }
                } catch (\Exception $e) {
                    error_log("[UniCore] In-app notification insert failed: " . $e->getMessage());
                }

                // ---- SMS Broadcast for opted-in users ----
                if (isset($data['send_sms_alert']) && ($data['send_sms_alert'] === 'true' || $data['send_sms_alert'] === true || $data['send_sms_alert'] === '1')) {
                    try {
                        // FIX: column is `phoneNum`, not `contactNumber`
                        $stmt = $db->query("SELECT phoneNum FROM users WHERE phoneNum IS NOT NULL AND phoneNum != '' AND lost_item_sms_notification = 1");
                        $phones = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        $smsMessage = "UniCore Alert: New lost item reported: $itemName. Check the portal!";
                        foreach ($phones as $phone) {
                            \Utils\SMSService::sendSMS($phone, $smsMessage);
                        }
                    } catch (\Exception $e) {
                        error_log("[UniCore SMS] Broadcast failed: " . $e->getMessage());
                    }
                }
                
                echo json_encode(['success' => true, 'lostID' => $lostID]);
            }
            
        } elseif ($method === 'PUT') {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!is_array($data) && !empty($_POST)) $data = $_POST; // Fallback
            if (!is_array($data)) $data = [];
            
           
            if (isset($data['update_preference'])) {
                if (!$this->validatePayload($data, [
                    'update_preference' => 'required|boolean',
                    'lost_item_sms_notification' => 'required|boolean',
                    'has_seen_lost_item_popup' => 'required|boolean'
                ])) {
                    return;
                }

                $db = \Config\Database::getInstance()->getConnection();
                
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

            if (!$this->validatePayload(['lostID' => $id], ['lostID' => 'required|positiveInteger'])) {
                return;
            }
            
            if (!isset($data['status']) || count($data) > 1) {
                if (!$this->validatePayload($data, [
                    'lostItemName' => 'required|string|maxLength:150',
                    'last_seen_place' => 'required|string|maxLength:150',
                    'last_seen_datetime' => 'required|string|dateFormat:Y-m-d\TH:i|beforeOrEqual:now',
                    'contact_number' => 'required|phone',
                    'description' => 'required|string|maxLength:1000',
                    'status' => 'nullable|string|in:lost,found,resolved'
                ])) {
                    return;
                }
            } elseif (!$this->validatePayload($data, [
                'status' => 'required|string|in:lost,found,resolved'
            ])) {
                return;
            }
            
            $model = new LostItem();
            $model->hydrateFromRequest($data);
            $model->setLostID($id);
            $model->setUserID($decoded->userID);
            $success = $model->update();
            echo json_encode(['success' => $success]);
            
        } elseif ($method === 'DELETE') {
            if (!$this->validatePayload(['lostID' => $id], ['lostID' => 'required|positiveInteger'])) {
                return;
            }
            $success = $model->delete($id, $decoded->userID);
            echo json_encode(['success' => $success]);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        }
    }

    private function validatePayload(array $data, array $rules): bool {
        $validator = new \Utils\Validator($data);
        $validator->validate($rules);

        if ($validator->passes()) {
            return true;
        }

        echo json_encode([
            'success' => false,
            'message' => $validator->getFirstError(),
            'errors' => $validator->getErrors()
        ]);
        return false;
    }
}
