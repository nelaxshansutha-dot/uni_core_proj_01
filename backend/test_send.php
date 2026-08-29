<?php
require 'vendor/autoload.php';

use Models\OtpVerification;

// Mock database connection by just including what's needed
// But maybe it's easier to just call the function manually
require 'utils/MailService.php';

$mailSent = \Utils\MailService::sendOTP('nelanelaxshan@gmail.com', '123456');
var_dump($mailSent);
