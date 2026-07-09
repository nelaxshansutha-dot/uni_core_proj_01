<?php
require_once __DIR__ . '/backend/config/Database.php';

$db = (new Database())->getConnection();
$userId = 8;
$otp = rand(100000, 999999);
echo "Generated OTP: $otp\n";

$stmt = $db->prepare("INSERT INTO OTP_verification (userID, otp_code, expired_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 MINUTE))");
if (!$stmt->execute([$userId, $otp])) {
    echo "Insert failed!\n";
    print_r($stmt->errorInfo());
    exit;
}

sleep(2);

$verifyOtp = (string)$otp;
$stmt2 = $db->prepare("SELECT * FROM OTP_verification WHERE userID = ? AND otp_code = ? AND expired_at > NOW() ORDER BY otpID DESC LIMIT 1");
if (!$stmt2->execute([$userId, $verifyOtp])) {
    echo "Select failed!\n";
    print_r($stmt2->errorInfo());
    exit;
}

$record = $stmt2->fetch(PDO::FETCH_ASSOC);

if ($record) {
    echo "OTP Verified!\n";
} else {
    echo "OTP Verification Failed!\n";
}
