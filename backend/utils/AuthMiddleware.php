<?php
require_once __DIR__ . '/Response.php';

class AuthMiddleware {
    public static function authenticate() {
        $authHeader = '';
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                $authHeader = $headers['Authorization'];
            }
        }
        
        if (empty($authHeader)) {
            Response::error("Unauthorized: Missing token", 401);
        }

        $token = str_replace('Bearer ', '', $authHeader);
        require_once __DIR__ . '/JWT.php';
        $decoded = JWT::verify($token);

        if (!$decoded || !isset($decoded['id'])) {
            Response::error("Unauthorized: Invalid or expired token", 401);
        }

        return $decoded; // Returns user details (id, role, etc)
    }

    public static function requireRole($user, $roles) {
        if (!in_array($user['role'], $roles)) {
            Response::error("Forbidden: Insufficient permissions", 403);
        }
    }
}
?>
