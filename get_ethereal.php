<?php
$ch = curl_init('https://api.nodemailer.com/user');
curl_setopt($ch, CURLOPT_POST, 1);
$payload = json_encode(['requestor' => 'UniCore', 'version' => '1.0']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
print_r(json_decode($res, true));
