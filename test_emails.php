<?php
require_once __DIR__ . '/backend/utils/MailService.php';

echo "Testing sendDeactivationEmail...\n";
$res1 = MailService::sendDeactivationEmail('jeevaa200320@gmail.com', 'Violated terms of service.');
echo $res1 ? "Success\n" : "Failed\n";

echo "Testing sendRepCredentialEmail...\n";
$res2 = MailService::sendRepCredentialEmail('jeevaa200320@gmail.com', 'Jeeva', 'Sutha', 'REP_123', 'TempPass123');
echo $res2 ? "Success\n" : "Failed\n";

echo "Check backend/mail_log.txt and backend/admin_log.txt for details.\n";
