<?php
namespace Utils;

class SMSService {
    public static function sendSMS($phoneNumbers, $message) {
        if (empty($phoneNumbers)) {
            return false;
        }

        // Convert to array if string
        if (!is_array($phoneNumbers)) {
            $phoneNumbers = explode(',', $phoneNumbers);
        }

        $validNumbers = [];
        foreach ($phoneNumbers as $phone) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (empty($phone)) continue;
            
            if (substr($phone, 0, 1) === '0') {
                $phone = '94' . substr($phone, 1);
            }
            $validNumbers[] = $phone;
        }

        if (empty($validNumbers)) return false;

        $recipientStr = implode(',', array_unique($validNumbers));

        $apiToken = "4810|NgGYVtUHjSS98YTck7nLSlYG9NgjUiv5agw5Enje1071d5c9";
        
        
        $url = "https://app.text.lk/api/v3/sms/send";

        $payload = [
            'recipient' => $recipientStr,
            'sender_id' => 'TextLKDemo', 
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

        file_put_contents(__DIR__ . '/../sms_log.txt', "[" . date('Y-m-d H:i:s') . "] TO: $recipientStr | HTTP: $httpCode | RESP: $response | MSG: $message\n", FILE_APPEND);

        return ($httpCode === 200 || $httpCode === 201);
    }
}
