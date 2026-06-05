//<?php
//require_once __DIR__ . '/utils/MailService.php';

////echo "Testing MailService...\n";

// $email = 'nelanelaxshan@gmail.com'; // sending to self for testing
// $otp = '123456';

// $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
// try {
//     $mail->SMTPDebug = 2; // Enable verbose debug output
//     $mail->isSMTP();
//     $mail->Host = 'smtp.gmail.com';
//     $mail->SMTPAuth = true;
//     $mail->Username = 'nelanelaxshan@gmail.com';
//     $mail->Password = 'yqyflurcewldkwix';
//     $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
//     $mail->Port = 587;
//     $mail->setFrom('nelanelaxshan@gmail.com', 'UniCore');
//     $mail->addAddress($email);
//     $mail->isHTML(true);
//     $mail->Subject = 'OTP Verification Test';
//     $mail->Body = "<h2>Test OTP: $otp</h2>";
//     $mail->send();
//     echo "Message has been sent\n";
// } catch (Exception $e) {
//     echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
// } catch (\Error $e) {
//     echo "Error: {$e->getMessage()}\n";
//}
