<?php
require_once __DIR__ . '/backend/config/Database.php';

// Simulate JSON post to auth.php
$url = "http://localhost/uni_core_proj_01/backend/api/auth.php?action=verify-otp";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'user_id' => 8,
    'otp' => '123456' // invalid, we expect it to fail
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

$response = curl_exec($ch);
curl_close($ch);
echo "Response: $response\n";
