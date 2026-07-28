<?php
namespace Controllers;
use Models\Notes;
use Middleware\AuthMiddleware;

class NotesController {
    public function handleRequest($method, $id = null, $action = null) {
        $decoded = AuthMiddleware::authenticate();
        $model = new Notes();

        if ($method === 'GET') {
            if ($action === 'search') {
                $q = $_GET['q'] ?? '';
                echo json_encode(['success' => true, 'data' => $model->search($q)]);
                return;
            }
            if ($action === 'download' && $id) {
                echo json_encode(['success' => true, 'url' => $model->download($id)]);
                return;
            }
            $filters = $_GET;
            $filters['enrollmentNo'] = $decoded->enrollmentNo ?? null;
            echo json_encode(['success' => true, 'data' => $model->view($id, $filters)]);
        } elseif ($method === 'POST') {
            $data = $_POST;
            $data['enrollmentNo'] = $decoded->enrollmentNo ?? $decoded->enrollment_no ?? null;
            $data['userID'] = $decoded->userID ?? null;
            $data['file_url'] = null; // Default value to prevent undefined key error
            
        
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/notes/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['file']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
                    $data['file_url'] = 'uploads/notes/' . $fileName;
                } else {
                    file_put_contents(__DIR__ . '/../error_log.txt', date('[Y-m-d H:i:s] ') . "Notes Upload Move Failed. From " . $_FILES['file']['tmp_name'] . " to " . $targetFile . "\n", FILE_APPEND);
                }
            } else {
                if (isset($_FILES['file'])) {
                    file_put_contents(__DIR__ . '/../error_log.txt', date('[Y-m-d H:i:s] ') . "Notes Upload File Error: " . $_FILES['file']['error'] . "\n", FILE_APPEND);
                } else {
                    file_put_contents(__DIR__ . '/../error_log.txt', date('[Y-m-d H:i:s] ') . "Notes Upload No File Provided in POST.\n", FILE_APPEND);
                }
            }

            if (!$data['file_url']) {
                echo json_encode(['success' => false, 'message' => 'File upload failed.']);
                return;
            }

            $nid = $model->upload($data);
            echo json_encode(['success' => true, 'noteID' => $nid]);
        } elseif ($method === 'PUT') {
            $data = json_decode(file_get_contents("php://input"), true);
            echo json_encode(['success' => $model->update($id, $data)]);
        } elseif ($method === 'DELETE') {
            echo json_encode(['success' => $model->delete($id)]);
        }
    }
}
