<?php
/**
 * JWT.php
 * Secure utility class for generating and verifying JSON Web Tokens (JWT) natively in PHP.
 * Uses HMAC-SHA256 signature, constant-time verification, and checks expiration.
 */
class JWT {
    // Secret key for signing the tokens. In production, load this from environment variables.
    private static $secret = "uni_core_proj_01_secure_secret_key_123456_!@#";

    /**
     * Encode data to Base64URL format
     */
    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Decode data from Base64URL format
     */
    private static function base64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    /**
     * Generate a new signature-verified JWT token
     * @param array $payload Key-value pairs to store in the token
     * @param int $expiryDuration Seconds until expiration (default 24 hours)
     * @return string Signed JWT token (header.payload.signature)
     */
    public static function generate($payload, $expiryDuration = 86400) {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        
        // Inject standard JWT claims
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiryDuration;

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Verify token signature and expiration
     * @param string $token Signed JWT token
     * @return array|false The decoded payload, or false if token is invalid or expired
     */
    public static function verify($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

        $header = json_decode(self::base64UrlDecode($base64UrlHeader), true);
        if (!$header || !isset($header['alg']) || $header['alg'] !== 'HS256') {
            return false;
        }

        $signature = self::base64UrlDecode($base64UrlSignature);
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);

        // Constant-time string comparison to prevent timing attacks
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }

        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);
        if (!$payload) {
            return false;
        }

        // Validate expiration claim (exp)
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false; // Token expired
        }

        return $payload;
    }
}
?>
