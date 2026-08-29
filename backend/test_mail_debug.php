<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'nelanelaxshan@gmail.com';
    $mail->Password   = 'mplabzddmccxqdjo';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('nelanelaxshan@gmail.com', 'UniCore');
    $mail->addAddress('nelanelaxshan@gmail.com'); // Send to self

    $mail->isHTML(true);
    $mail->Subject = 'Test Debug';
    $mail->Body    = 'This is a test email.';

    $mail->send();
    echo "Message has been sent\n";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
