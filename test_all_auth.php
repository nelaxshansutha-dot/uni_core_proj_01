<?php
// test_all_auth.php

$baseUrl = 'http://localhost/uni_core_proj_01/backend/api/auth.php';

function makeRequest($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    $response = curl_exec($ch);
    curl_close($ch);
    $decoded = json_decode($response, true);
    if ($decoded === null) {
        echo "Raw response: " . $response . "\n";
    }
    return $decoded;
}

function getLatestOtp($userId) {
    require_once __DIR__ . '/backend/config/Database.php';
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT otp_code FROM OTP_verification WHERE userID = ? ORDER BY otpID DESC LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function deleteUser($email) {
    require_once __DIR__ . '/backend/config/Database.php';
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("DELETE FROM Users WHERE email = ?");
    $stmt->execute([$email]);
}

$testEmail = 'teststudent' . time() . '@std.uwu.ac.lk';
$testEnrollment = 'UWU/CST/21/' . rand(1000, 9999);
$testPassword = 'Password123';

echo "1. Testing Registration...\n";
$regData = [
    'role' => 'student',
    'first_name' => 'Test',
    'last_name' => 'Student',
    'enrollment_no' => $testEnrollment,
    'email' => $testEmail,
    'phone_number' => '0771234567',
    'password' => $testPassword,
    'confirm_password' => $testPassword
];

$regRes = makeRequest("$baseUrl?action=register", $regData);
if ($regRes['status'] === 'success') {
    echo " -> Registration successful. UserID: " . $regRes['data']['user_id'] . "\n";
    $userId = $regRes['data']['user_id'];
} else {
    echo " -> Registration failed: " . json_encode($regRes) . "\n";
    exit;
}

echo "2. Testing Resend OTP...\n";
$resendRes = makeRequest("$baseUrl?action=resend-otp", ['user_id' => $userId]);
if ($resendRes['status'] === 'success') {
    echo " -> Resend OTP successful.\n";
} else {
    echo " -> Resend OTP failed: " . json_encode($resendRes) . "\n";
    exit;
}

echo "3. Fetching OTP from database...\n";
$otp = getLatestOtp($userId);
echo " -> OTP: $otp\n";

echo "4. Testing Verify OTP...\n";
$verifyRes = makeRequest("$baseUrl?action=verify-otp", ['user_id' => $userId, 'otp' => $otp]);
if ($verifyRes['status'] === 'success') {
    echo " -> OTP Verification successful. Token received.\n";
} else {
    echo " -> OTP Verification failed: " . json_encode($verifyRes) . "\n";
    exit;
}

echo "5. Testing Login...\n";
$loginData = [
    'role' => 'student',
    'enrollment_no' => $testEnrollment,
    'password' => $testPassword
];
$loginRes = makeRequest("$baseUrl?action=login", $loginData);
if ($loginRes['status'] === 'success') {
    echo " -> Login successful. Token received.\n";
} else {
    echo " -> Login failed: " . json_encode($loginRes) . "\n";
    exit;
}

echo "6. Testing Forgot Password...\n";
$forgotRes = makeRequest("$baseUrl?action=forgot-password", ['email' => $testEmail]);
if ($forgotRes['status'] === 'success') {
    echo " -> Forgot Password successful.\n";
} else {
    echo " -> Forgot Password failed: " . json_encode($forgotRes) . "\n";
    exit;
}

echo "7. Fetching Reset OTP from database...\n";
$resetOtp = getLatestOtp($userId);
echo " -> Reset OTP: $resetOtp\n";

echo "8. Testing Verify Reset OTP...\n";
$verifyResetRes = makeRequest("$baseUrl?action=verify-reset-otp", ['user_id' => $userId, 'otp' => $resetOtp]);
if ($verifyResetRes['status'] === 'success') {
    echo " -> Verify Reset OTP successful. Reset token: " . $verifyResetRes['data']['reset_token'] . "\n";
    $resetToken = $verifyResetRes['data']['reset_token'];
} else {
    echo " -> Verify Reset OTP failed: " . json_encode($verifyResetRes) . "\n";
    exit;
}

echo "9. Testing Reset Password...\n";
$newPassword = 'NewPassword123';
$resetPassData = [
    'user_id' => $userId,
    'reset_token' => $resetToken,
    'new_password' => $newPassword,
    'confirm_password' => $newPassword
];
$resetPassRes = makeRequest("$baseUrl?action=reset-password", $resetPassData);
if ($resetPassRes['status'] === 'success') {
    echo " -> Reset Password successful.\n";
} else {
    echo " -> Reset Password failed: " . json_encode($resetPassRes) . "\n";
    exit;
}

echo "10. Testing Login with New Password...\n";
$loginData2 = [
    'role' => 'student',
    'enrollment_no' => $testEnrollment,
    'password' => $newPassword
];
$loginRes2 = makeRequest("$baseUrl?action=login", $loginData2);
if ($loginRes2['status'] === 'success') {
    echo " -> Login with new password successful.\n";
} else {
    echo " -> Login with new password failed: " . json_encode($loginRes2) . "\n";
    exit;
}

echo "Cleaning up test user...\n";
deleteUser($testEmail);

echo "\nALL AUTHENTICATION FUNCTIONS ARE WORKING CORRECTLY!\n";
