<?php
require_once __DIR__ . '/backend/config/Database.php';

$db = (new Database())->getConnection();
$userId = 8;
$otp = "111222";

$db->prepare("DELETE FROM OTP_verification WHERE userID = ?")->execute([$userId]);
$stmt = $db->prepare("INSERT INTO OTP_verification (userID, otp_code, expired_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 MINUTE))");
$stmt->execute([$userId, $otp]);

echo "OTP inserted. Now verifying via API...\n";

// Now call the local API
$url = "http://localhost/uni_core_proj_01/backend/api/auth.php?action=verify-otp";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'user_id' => $userId,
    'otp' => $otp
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

$response = curl_exec($ch);
curl_close($ch);

echo "Response from API: $response\n";
