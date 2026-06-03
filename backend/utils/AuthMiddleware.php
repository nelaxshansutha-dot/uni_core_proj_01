<?php
require_once __DIR__ . '/Response.php';

class AuthMiddleware {
    public static function authenticate() {
        $headers = apache_request_headers();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (empty($authHeader)) {
            Response::error("Unauthorized: Missing token", 401);
        }

        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = json_decode(base64_decode($token), true);

        if (!$decoded || !isset($decoded['id'])) {
            Response::error("Unauthorized: Invalid token", 401);
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
