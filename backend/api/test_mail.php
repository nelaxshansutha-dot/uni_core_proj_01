<?php

require_once '../utils/MailService.php';

$email = "nelanelaxshan@gmail.com";
$otp = rand(100000,999999);

if(MailService::sendOTP($email,$otp))
{
    echo "OTP Sent Successfully";
}
else
{
    echo "Failed";
}