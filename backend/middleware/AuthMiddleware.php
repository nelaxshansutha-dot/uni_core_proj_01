<?php

namespace Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use Config\Database;
use PDO;

class AuthMiddleware {
    
    // In a real app, this should be in .env. Hardcoded fallback for now.
    private static $secret_key = "YOUR_SUPER_SECRET_KEY"; 
    
    public static function getSecretKey() {
        return $_ENV['JWT_SECRET'] ?? self::$secret_key;
    }

    /**
     * Protect route. Validates JWT and optionally checks allowed roles.
     * Returns decoded token payload if valid, otherwise dies with JSON response.
     */
    public static function authenticate(array $allowedRoles = []) {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Unauthorized. Token not found."]);
            exit;
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key(self::getSecretKey(), 'HS256'));
            
            // Check if token is revoked
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id FROM revoked_tokens WHERE jti = :jti LIMIT 1");
            $stmt->bindParam(':jti', $decoded->jti);
            $stmt->execute();
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Unauthorized. Token has been revoked."]);
                exit;
            }

            // Role check
            if (!empty($allowedRoles) && !in_array($decoded->role, $allowedRoles)) {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Forbidden. Insufficient permissions."]);
                exit;
            }

            // Return user context
            return $decoded;

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Unauthorized. Invalid or expired token.", "error" => $e->getMessage()]);
            exit;
        }
    }
}
