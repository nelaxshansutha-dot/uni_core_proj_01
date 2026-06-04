<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    public static function sendOTP($email, $otp)
    {
        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;

            $mail->Username = 'nelanelaxshan@gmail.com';
            $mail->Password = 'yqyflurcewldkwix';

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('nelanelaxshan@gmail.com', 'UniCore');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'OTP Verification';
            $mail->Body = "
                <h2>UniCore OTP Verification</h2>
                <p>Your OTP is:</p>
                <h1>$otp</h1>
                <p>Valid for 5 minutes.</p>
            ";

            $mail->send();
            return true;

        } catch (Exception $e) {
            return false;
        }
    }
}