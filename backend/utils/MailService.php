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
              <div style='font-family:Arial,sans-serif; background:#f4f6f9; padding:30px;'>

    <div style='max-width:420px; margin:auto;
                background:linear-gradient(135deg, #6a0dad, #7a3d70);
                border-radius:14px; padding:30px;
                box-shadow:0 8px 25px rgba(0,0,0,0.2);
                text-align:center; color:white;'>

        <!-- LOGO -->
        <img src='http://localhost/uni_core_proj_01/public/logo.png'
             style='width:80px; height:80px; border-radius:10px; margin-bottom:10px;' />

        <h2 style='margin:0;'>UniCore</h2>
        <p style='margin:5px 0 15px; opacity:0.85; font-size:13px;'>
            Smart Campus Utility Hub
        </p>

        <hr style='border:0; height:1px; background:rgba(255,255,255,0.3); margin:15px 0;'>

        <h3>OTP Verification</h3>

        <p style='font-size:14px; opacity:0.9;'>
            Use the OTP below to verify your account
        </p>

        <div style='font-size:34px; font-weight:bold; letter-spacing:6px;
                    background:rgba(255,255,255,0.2);
                    padding:12px 20px;
                    border-radius:10px;
                    display:inline-block;
                    margin:20px 0;'>
            {$otp}
        </div>

        <p style='font-size:14px; font-weight:bold; color:#ffe6e6;'>
            ⏳ This OTP is valid for 2 minutes
        </p>

        <p style='font-size:11px; opacity:0.8; margin-top:20px;'>
            If you did not request this email, please ignore it.
        </p>

    </div>

</div>
            ";

            $mail->send();

            // Also log to file for local testing
            $logPath = __DIR__ . '/../otp_log.txt';
            file_put_contents($logPath, date('Y-m-d H:i:s') . " | OTP for {$email}: {$otp}" . PHP_EOL, FILE_APPEND);

            return true;

        } catch (Exception $e) {
            // Always write to log file so OTP is accessible during local development
            $logPath = __DIR__ . '/../otp_log.txt';
            file_put_contents($logPath, date('Y-m-d H:i:s') . " | OTP for {$email}: {$otp} [SMTP ERROR: {$mail->ErrorInfo}]" . PHP_EOL, FILE_APPEND);

            // Return false but don't crash — OTP is still saved in DB
            return false;
        }
    }
}