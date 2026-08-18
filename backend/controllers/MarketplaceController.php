<?php
namespace Controllers;
use Models\Marketplace;
use Middleware\AuthMiddleware;

class MarketplaceController {
    public function handleRequest($method, $id = null, $action = null) {
        $decoded = AuthMiddleware::authenticate();
        $model = new Marketplace();

        $requiresProductID = in_array($method, ['PUT', 'DELETE'], true)
            || ($method === 'POST' && $action === 'flag');
        if (($requiresProductID || $id !== null) && !$this->validateProductID($id)) {
            return;
        }

        if ($method === 'GET') {
            echo json_encode(['success' => true, 'data' => $model->view($id)]);
        } elseif ($method === 'POST') {
            if ($action === 'flag' && $id) {
                echo json_encode(['success' => $model->flag($id)]);
                return;
            }
            
            $data = json_decode(file_get_contents("php://input"), true) ?? [];
            
            $validator = new \Utils\Validator($data);
            $validator->validate([
                'productName' => 'required|string|maxLength:150',
                'price' => 'required|numeric|decimal:2|min:0|max:99999999.99',
                'condition_type' => 'required|string|in:new,used',
                'location' => 'required|string|maxLength:150',
                'phone_number' => 'required|phone',
                'description' => 'required|string|maxLength:1000',
                'usage_duration' => 'requiredIf:condition_type,used|nullable|string|maxLength:50',
                'image_url' => 'nullable|string|url|maxLength:255',
                'image_url2' => 'nullable|string|url|maxLength:255',
                'image_url3' => 'nullable|string|url|maxLength:255',
                'image_url4' => 'nullable|string|url|maxLength:255'
            ]);

            if (!$validator->passes()) {
                echo json_encode([
                    'success' => false,
                    'message' => $validator->getFirstError(),
                    'errors' => $validator->getErrors()
                ]);
                return;
            }

            $model = new Marketplace();
            $model->hydrateFromRequest($data);
            $model->setUserID($decoded->userID);
            $pid = $model->create();
            echo json_encode(['success' => true, 'productID' => $pid]);
            
        } elseif ($method === 'PUT') {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!is_array($data) && !empty($_POST)) $data = $_POST; // Fallback
            if (!is_array($data)) $data = [];
            
            // Only validate if status is not the only thing being updated (mark as sold)
            if (!isset($data['status']) || count($data) > 1) {
                $validator = new \Utils\Validator($data);
                $validator->validate([
                    'productName' => 'required|string|maxLength:150',
                    'price' => 'required|numeric|decimal:2|min:0|max:99999999.99',
                    'condition_type' => 'required|string|in:new,used',
                    'location' => 'required|string|maxLength:150',
                    'phone_number' => 'required|phone',
                    'description' => 'required|string|maxLength:1000',
                    'usage_duration' => 'requiredIf:condition_type,used|nullable|string|maxLength:50',
                    'image_url' => 'nullable|string|url|maxLength:255',
                    'image_url2' => 'nullable|string|url|maxLength:255',
                    'image_url3' => 'nullable|string|url|maxLength:255',
                    'image_url4' => 'nullable|string|url|maxLength:255'
                ]);

                if (!$validator->passes()) {
                    echo json_encode([
                        'success' => false,
                        'message' => $validator->getFirstError(),
                        'errors' => $validator->getErrors()
                    ]);
                    return;
                }
            } else {
                $validator = new \Utils\Validator($data);
                $validator->validate(['status' => 'required|string|in:available,sold']);
                if (!$validator->passes()) {
                    echo json_encode([
                        'success' => false,
                        'message' => $validator->getFirstError(),
                        'errors' => $validator->getErrors()
                    ]);
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
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        }
    }

    private function validateProductID($id): bool {
        $validator = new \Utils\Validator(['productID' => $id]);
        $validator->validate(['productID' => 'required|positiveInteger']);

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
