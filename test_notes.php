<?php
require 'backend/vendor/autoload.php';
require 'backend/config/Database.php';
require 'backend/models/Notes.php';
$model = new \Models\Notes();
$res = $model->view(null, ['enrollmentNo' => 'UWU/CST/23/088']);
echo json_encode($res, JSON_PRETTY_PRINT);
