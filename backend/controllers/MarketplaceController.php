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
            
            $validator = new \Utils\Validator($data);
            $validator->validate([
                'productName' => 'required|maxLength:150',
                'price' => 'required|numeric|min:0',
                'condition_type' => 'required|in:new,used',
                'location' => 'required|maxLength:150',
                'phone_number' => 'required|phone',
                'description' => 'required|maxLength:1000'
            ]);

            if (!$validator->passes()) {
                echo json_encode(['success' => false, 'message' => $validator->getFirstError()]);
                return;
            }

            if ($data['condition_type'] === 'used' && empty($data['usage_duration'])) {
                echo json_encode(['success' => false, 'message' => 'Usage duration is required for used items.']);
                return;
            }

            $model = new Marketplace();
            $model->hydrateFromRequest($data);
            $model->setUserID($decoded->userID);
            $pid = $model->create();
            echo json_encode(['success' => true, 'productID' => $pid]);
            
        } elseif ($method === 'PUT') {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) $data = $_POST; // Fallback
            
            // Only validate if status is not the only thing being updated (mark as sold)
            if (!isset($data['status']) || count($data) > 1) {
                $validator = new \Utils\Validator($data);
                $validator->validate([
                    'productName' => 'required|maxLength:150',
                    'price' => 'required|numeric|min:0',
                    'condition_type' => 'required|in:new,used',
                    'location' => 'required|maxLength:150',
                    'phone_number' => 'required|phone',
                    'description' => 'required|maxLength:1000'
                ]);

                if (!$validator->passes()) {
                    echo json_encode(['success' => false, 'message' => $validator->getFirstError()]);
                    return;
                }
                
                if ($data['condition_type'] === 'used' && empty($data['usage_duration'])) {
                    echo json_encode(['success' => false, 'message' => 'Usage duration is required for used items.']);
                    return;
                }
            }

            $model = new Marketplace();
            $model->hydrateFromRequest($data);
            $model->setProductID($id);
            $model->setUserID($decoded->userID);
            $success = $model->update();
            echo json_encode(['success' => $success]);
            
        } elseif ($method === 'DELETE') {
            $success = $model->delete($id, $decoded->userID);
            echo json_encode(['success' => $success]);
        }
    }
}
