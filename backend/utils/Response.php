<?php
namespace Utils;

class Response {
    
    
    public static function success($data = null, $message = "Request successful", $statusCode = 200) {
        http_response_code($statusCode);
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit; 
    }

 
    public static function error($message = "An error occurred", $statusCode = 400, $errors = null) {
        http_response_code($statusCode);
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit; 
    }
}
