<?php
require_once __DIR__ . '/../utils/Response.php';


abstract class BaseController {

    protected function jsonResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    protected function errorResponse($message, $status = 400) {
        Response::error($message, $status);
    }
}
?>
