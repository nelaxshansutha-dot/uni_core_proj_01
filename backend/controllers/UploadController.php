<?php
namespace Controllers;
use Middleware\AuthMiddleware;

class UploadController {
    public function handleRequest($method) {
        $decoded = AuthMiddleware::authenticate();

        if ($method === 'POST') {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/general/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['image']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $url = 'uploads/general/' . $fileName;
                    echo json_encode([
                        'status' => 'success',
                        'success' => true,
                        'data' => ['url' => $url]
                    ]);
                    return;
                }
            }
            
            http_response_code(400);
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Upload failed']);
        } else {
            http_response_code(405);
            echo json_encode(['message' => 'Method not allowed']);
        }
    }
}
