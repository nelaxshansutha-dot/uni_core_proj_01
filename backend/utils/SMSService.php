<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../config/Database.php';

class SMSService {
    public static function sendSMS($phoneNumber, $message) {
        if (empty($phoneNumber)) {
            return false;
        }

        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '94' . substr($phoneNumber, 1);
        }

        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT api_token, api_url, sender_id FROM sms_settings LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            return false;
        }

        $apiToken = $settings['api_token'];
        $url = $settings['api_url'];

        $payload = [
            'recipient' => $phoneNumber,
            'sender_id' => $settings['sender_id'], 
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

        file_put_contents(__DIR__ . '/../sms_log.txt', "[" . date('Y-m-d H:i:s') . "] TO: $phoneNumber | HTTP: $httpCode | RESP: $response | MSG: $message\n", FILE_APPEND);

        return ($httpCode === 200 || $httpCode === 201);
    }
}
?>
