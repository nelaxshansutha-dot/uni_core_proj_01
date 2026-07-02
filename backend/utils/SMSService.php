<?php
require_once __DIR__ . '/../utils/Response.php';

class SMSService {
    public static function sendSMS($phoneNumber, $message) {
        if (empty($phoneNumber)) {
            return false;
        }

        // Clean phone number (e.g. remove spaces, dashes, + signs)
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Convert leading 0 to 94 if local Sri Lankan number
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '94' . substr($phoneNumber, 1);
        }

        $apiToken = "4810|NgGYVtUHjSS98YTck7nLSlYG9NgjUiv5agw5Enje1071d5c9";
        
        // Target endpoint
        $url = "https://app.text.lk/api/v3/sms/send";

        $payload = [
            'recipient' => $phoneNumber,
            'sender_id' => 'TextLKDemo', // text.lk authorized sandbox sender ID
            'message' => $message
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $apiToken",
            "Content-Type: application/json",
            "Accept: application/json"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log the response internally for debugging/auditing
        file_put_contents(__DIR__ . '/../sms_log.txt', "[" . date('Y-m-d H:i:s') . "] TO: $phoneNumber | HTTP: $httpCode | RESP: $response | MSG: $message\n", FILE_APPEND);

        return ($httpCode === 200 || $httpCode === 201);
    }
}
?>
