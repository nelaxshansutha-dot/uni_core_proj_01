<?php
require_once __DIR__ . '/Response.php';

class AuthMiddleware {
    public static function authenticate($returnFalseOnFail = false) {
        $token = '';
        
        // 1. Check for HttpOnly Cookie first
        if (isset($_COOKIE['auth_token'])) {
            $token = $_COOKIE['auth_token'];
        } else {
            // 2. Fallback to Authorization header
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
            if (!empty($authHeader)) {
                $token = str_replace('Bearer ', '', $authHeader);
            }
        }
        
        
        if (empty($token)) {
            error_log("AuthMiddleware: Missing token. Cookie present: " . (isset($_COOKIE['auth_token']) ? 'Yes' : 'No'));
            if ($returnFalseOnFail) return false;
            Response::error("Unauthorized: Missing token", 401);
        }
        require_once __DIR__ . '/JWT.php';
        try {
            $decoded = JWT::verify($token);
        } catch (Exception $e) {
            error_log("AuthMiddleware: JWT verify threw exception - " . $e->getMessage());
            $decoded = false;
        }

        if (!$decoded || !isset($decoded['id'])) {
            error_log("AuthMiddleware: Invalid or expired token. Token: $token");
            if ($returnFalseOnFail) return false;
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
