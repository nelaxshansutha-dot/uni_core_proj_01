<?php
require 'vendor/autoload.php';
require 'utils/MailService.php';

$res = Utils\MailService::sendOTP('nelanelaxshan@gmail.com', '123456');
var_dump($res);
