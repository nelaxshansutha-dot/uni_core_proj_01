<?php
require 'vendor/autoload.php';

// Mock request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/auth/resend-otp';

// We need to pass JSON data via php://input, but since we can't easily mock php://input in CLI without streams, 
// let's just initialize the controller directly.

// Load env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$controller = new \Controllers\AuthController();

// Instead of calling resendOtp() which uses php://input, I'll write a small reflection to set it, or just write a standalone script to call sendOTP twice.

require 'utils/MailService.php';

$res1 = \Utils\MailService::sendOTP('nelanelaxshan@gmail.com', '111111');
echo "First send: " . var_export($res1, true) . "\n";

$res2 = \Utils\MailService::sendOTP('nelanelaxshan@gmail.com', '222222');
echo "Second send: " . var_export($res2, true) . "\n";

