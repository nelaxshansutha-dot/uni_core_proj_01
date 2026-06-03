<?php
class Response {
    public static function json($status, $message, $data = null, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'status' => $status,
            'message' => $message,
            'data' => $data
        ];
        
        echo json_encode($response);
        exit();
    }

    public static function success($message, $data = null) {
        self::json('success', $message, $data, 200);
    }

    public static function error($message, $httpCode = 400) {
        self::json('error', $message, null, $httpCode);
    }
}
?>
