<?php

require_once __DIR__ . '/../config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private static function configureMailer() {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $_ENV['SMTP_PORT'] ?? 465;
        
        $mail->setFrom($_ENV['SMTP_USER'], 'UniCore');
        return $mail;
    }

    public static function sendOTP($email, $otp)
    {
        try {
            $mail = self::configureMailer();
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

    public static function sendDeactivationEmail($email, $reason)
    {
        try {
            $mail = self::configureMailer();
            $mail->setFrom($_ENV['SMTP_USER'], 'UniCore Admin');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Account Deactivated';
            $mail->Body = "
              <div style='font-family:Arial,sans-serif; background:#f4f6f9; padding:30px;'>
                <div style='max-width:420px; margin:auto; background:white; border-radius:14px; padding:30px; box-shadow:0 8px 25px rgba(0,0,0,0.2); text-align:center;'>
                    <h2 style='color:#dc3545; margin:0;'>Account Deactivated</h2>
                    <hr style='border:0; height:1px; background:#ddd; margin:15px 0;'>
                    <p style='font-size:16px; color:#333; text-align:left;'>Your UniCore account has been deactivated by an administrator.</p>
                    <p style='font-size:14px; color:#555; text-align:left;'><strong>Reason:</strong><br/>" . nl2br(htmlspecialchars($reason)) . "</p>
                    <p style='font-size:13px; color:#777; text-align:left; margin-top:20px;'>If you believe this is a mistake, please contact support.</p>
                </div>
              </div>
            ";
            $mail->send();
            return true;
        } catch (Exception $e) {
            $logPath = __DIR__ . '/../mail_log.txt';
            file_put_contents($logPath, date('Y-m-d H:i:s') . " | Deactivation Email to {$email} failed [SMTP ERROR: {$mail->ErrorInfo}]" . PHP_EOL, FILE_APPEND);
            return false;
        }
    }

    public static function sendRepCredentialEmail($email, $fname, $lname, $rep_id, $password)
    {
        try {
            $mail = self::configureMailer();
            $mail->setFrom($_ENV['SMTP_USER'], 'UniCore Admin');
            $mail->addAddress($email, $fname);

            $html = "
                <div style='font-family: Arial, sans-serif; padding: 20px;'>
                    <h1 style='color: #6a0dad; border-bottom: 2px solid #6a0dad; padding-bottom: 10px;'>UniCore Course Representative Appointment</h1>
                    <p>Dear {$fname} {$lname},</p>
                    <p>Congratulations! You have been officially appointed as a Course Representative.</p>
                    <p>Below are your exclusive Rep Dashboard login credentials:</p>
                    <div style='background: #f4f6f9; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p><strong>Rep ID:</strong> {$rep_id}</p>
                        <p><strong>Temporary Password:</strong> {$password}</p>
                    </div>
                    <p><em>Note: Your standard student login (Enrollment Number) remains active for accessing the Student Dashboard.</em></p>
                    <br/>
                    <p>Best Regards,</p>
                    <p><strong>The UniCore Admin Team</strong></p>
                </div>
            ";

            $mail->isHTML(true);
            $mail->Subject = 'Official Course Representative Appointment';
            $mail->Body    = $html;
            $mail->send();
            return true;
        } catch (Exception $e) {
            $logPath = __DIR__ . '/../admin_log.txt';
            $logMsg = date('Y-m-d H:i:s') . " | Failed to send email to {$email}: {$mail->ErrorInfo}\n";
            $logMsg .= "Local Dev Credentials - Rep ID: {$rep_id}, Temp Password: {$password}\n";
            file_put_contents($logPath, $logMsg, FILE_APPEND);
            return false;
        }
    }
}