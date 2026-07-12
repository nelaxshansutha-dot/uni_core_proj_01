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
            $data['enrollmentNo'] = $decoded->enrollmentNo ?? null;
            
            // Handle File Upload
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/notes/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['file']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
                    $data['file_url'] = 'uploads/notes/' . $fileName;
                }
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
