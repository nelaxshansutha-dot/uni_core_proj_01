<?php
namespace Controllers;
use Models\Marketplace;
use Middleware\AuthMiddleware;

class MarketplaceController {
    public function handleRequest($method, $id = null, $action = null) {
        $decoded = AuthMiddleware::authenticate();
        $model = new Marketplace();

        if ($method === 'GET') {
            echo json_encode(['success' => true, 'data' => $model->view($id)]);
        } elseif ($method === 'POST') {
            if ($action === 'flag' && $id) {
                echo json_encode(['success' => $model->flag($id)]);
                return;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            $data['userID'] = $decoded->userID;
            
            $pid = $model->create($data);
            echo json_encode(['success' => true, 'productID' => $pid]);
            
        } elseif ($method === 'PUT') {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) $data = $_POST; // Fallback
            $success = $model->update($id, $decoded->userID, $data);
            echo json_encode(['success' => $success]);
            
        } elseif ($method === 'DELETE') {
            $success = $model->delete($id, $decoded->userID);
            echo json_encode(['success' => $success]);
        }
    }
}
