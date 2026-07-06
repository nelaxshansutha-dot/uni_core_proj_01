<?php
require_once __DIR__ . '/backend/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'nelanelaxshan@gmail.com';
    $mail->Password = 'yqyflurcewldkwix';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->SMTPDebug = 2; // Enable verbose debug output
    
    $mail->setFrom('nelanelaxshan@gmail.com', 'Test');
    $mail->addAddress('nelanelaxshan@gmail.com');
    $mail->Subject = 'Test SMTPS';
    $mail->Body = 'Test';
    $mail->send();
    echo "Sent using SMTPS/465\n";
} catch (Exception $e) {
    echo "Error with SMTPS/465: " . $mail->ErrorInfo . "\n";
}
