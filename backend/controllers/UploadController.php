<?php
namespace Controllers;
use Middleware\AuthMiddleware;

class UploadController {
    public function handleRequest($method) {
        $decoded = AuthMiddleware::authenticate();

        if ($method === 'POST') {
            if (isset($_FILES['image'])) {
                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
                    ];
                    $errorMsg = $uploadErrors[$_FILES['image']['error']] ?? 'Unknown upload error';
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Upload failed: ' . $errorMsg]);
                    return;
                }
                
                // Validate Image MIME Type
                $mimeType = mime_content_type($_FILES['image']['tmp_name']);
                $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                
                if (!in_array($mimeType, $allowedImageTypes)) {
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and WebP are allowed.']);
                    return;
                }

                try {
                    $url = \Services\CloudinaryUploader::upload($_FILES['image']['tmp_name'], 'image');
                    echo json_encode([
                        'status' => 'success',
                        'success' => true,
                        'data' => ['url' => $url]
                    ]);
                    return;
                } catch (\Exception $e) {
                    file_put_contents(__DIR__ . '/../error_log.txt', date('[Y-m-d H:i:s] ') . "UploadController Cloudinary Error: " . $e->getMessage() . "\n", FILE_APPEND);
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'success' => false, 'message' => $e->getMessage()]);
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
