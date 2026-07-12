<?php

namespace Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailService {

    private static function getMailer(): ?PHPMailer {
        $mail = new PHPMailer(true);
        try {
            // Load .env if not already loaded
            if (empty($_ENV['SMTP_USER']) && file_exists(__DIR__ . '/../.env')) {
                $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    if (strpos($line, '=') !== false) {
                        [$key, $val] = explode('=', $line, 2);
                        $_ENV[trim($key)] = trim($val);
                        putenv(trim($key) . '=' . trim($val));
                    }
                }
            }

            $host = $_ENV['SMTP_HOST'] ?? $_SERVER['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $user = $_ENV['SMTP_USER'] ?? $_SERVER['SMTP_USER'] ?? getenv('SMTP_USER') ?: '';
            $pass = $_ENV['SMTP_PASS'] ?? $_SERVER['SMTP_PASS'] ?? getenv('SMTP_PASS') ?: '';
            $port = (int)($_ENV['SMTP_PORT'] ?? $_SERVER['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 587);

            if (empty($user) || empty($pass)) {
                error_log('[UniCore MailService] SMTP_USER or SMTP_PASS not set in .env');
                return null;
            }

            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $user;
            $mail->Password   = $pass;
            // port 465 = SSL, everything else (587, 2525) = STARTTLS
            $mail->SMTPSecure = ($port === 465)
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $port;
            $mail->CharSet    = 'UTF-8';

            // Disable SSL peer verification (safe for Brevo/relay servers on localhost)
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ]
            ];

            $mail->setFrom($user, 'UniCore');

            return $mail;

        } catch (Exception $e) {
            error_log('[UniCore MailService] Configuration error: ' . $e->getMessage());
            return null;
        }
    }

    public static function sendOTP(string $toEmail, string $otpCode): bool {
        $mail = self::getMailer();
        if (!$mail) return false;

        try {
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'Your UniCore OTP Verification Code';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 480px; margin: auto; padding: 30px; border: 1px solid #e0e0e0; border-radius: 10px; color: #333;'>
                    <h2 style='color: #4f46e5;'>UniCore — Email Verification</h2>
                    <p>Thank you for registering. Use the OTP below to verify your account:</p>
                    <div style='font-size: 36px; font-weight: bold; letter-spacing: 12px; background: #f3f4f6; padding: 16px 24px; border-radius: 8px; display: inline-block; margin: 16px 0;'>
                        {$otpCode}
                    </div>
                    <p style='color: #6b7280; font-size: 14px;'>This OTP expires in <strong>15 minutes</strong>. Do not share it with anyone.</p>
                    <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;'>
                    <p style='font-size: 12px; color: #9ca3af;'>If you did not register for UniCore, please ignore this email.</p>
                </div>
            ";
            $mail->AltBody = "Your UniCore OTP is: {$otpCode}. It expires in 15 minutes.";

            $result = $mail->send();
            if (!$result) {
                error_log('[UniCore MailService] OTP send failed: ' . $mail->ErrorInfo);
            }
            return $result;
        } catch (Exception $e) {
            error_log('[UniCore MailService] OTP send exception: ' . $mail->ErrorInfo);
            return false;
        }
    }

    public static function sendDeactivationEmail(string $toEmail, string $reason): bool {
        $mail = self::getMailer();
        if (!$mail) return false;

        try {
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'UniCore Account Deactivated';
            $mail->Body    = "<p>Your UniCore account has been deactivated.</p><p><strong>Reason:</strong> {$reason}</p>";
            return $mail->send();
        } catch (Exception $e) {
            error_log('[UniCore MailService] Deactivation email error: ' . $mail->ErrorInfo);
            return false;
        }
    }

    public static function sendRepCredentialEmail(string $toEmail, string $fname, string $lname, string $repIdStr, string $tempPass): bool {
        $mail = self::getMailer();
        if (!$mail) return false;

        try {
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'UniCore — Course Representative Credentials';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 24px; color: #333;'>
                    <h2 style='color: #4f46e5;'>Welcome, {$fname} {$lname}!</h2>
                    <p>You have been assigned as a <strong>Course Representative</strong> on UniCore.</p>
                    <table style='margin-top:16px; border-collapse: collapse;'>
                        <tr><td style='padding: 6px 16px 6px 0; color:#6b7280;'>Rep ID:</td><td><strong>{$repIdStr}</strong></td></tr>
                        <tr><td style='padding: 6px 16px 6px 0; color:#6b7280;'>Temp Password:</td><td><strong>{$tempPass}</strong></td></tr>
                    </table>
                    <p style='margin-top:16px; font-size:13px; color:#9ca3af;'>Please log in and change your password immediately.</p>
                </div>
            ";
            return $mail->send();
        } catch (Exception $e) {
            error_log('[UniCore MailService] Rep credential email error: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
