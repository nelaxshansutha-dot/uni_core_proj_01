<?php
require_once __DIR__ . '/../utils/Response.php';

// Inheritance & Abstraction: Base class for all controllers
abstract class BaseController {
    
    // Abstract method forcing child controllers to implement a main handler
    // abstract public function handleRequest($action, $data);
    
    // Common inherited method for sending JSON response
    protected function jsonResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    // Common inherited method for error response
    protected function errorResponse($message, $status = 400) {
        Response::error($message, $status);
    }
}
?>
