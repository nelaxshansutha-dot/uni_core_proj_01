<?php
require 'backend/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/backend/');
$dotenv->load();
require 'backend/Config/Database.php';

$db = \Config\Database::getInstance()->getConnection();

$db->exec("SET FOREIGN_KEY_CHECKS = 0;");
$db->exec("DROP TABLE IF EXISTS peer_learning_request;");

$db->exec("CREATE TABLE peer_learning_request (
    requestID INT AUTO_INCREMENT PRIMARY KEY,
    repID INT NULL,
    enrollmentNo VARCHAR(50) NOT NULL,
    courseUnitID VARCHAR(20) NOT NULL,
    courseUnitName VARCHAR(200) NOT NULL,
    description TEXT,
    std_year INT NULL,
    semester INT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repID) REFERENCES Course_representative(repID) ON DELETE CASCADE,
    FOREIGN KEY (enrollmentNo) REFERENCES Student(enrollmentNo) ON DELETE CASCADE,
    FOREIGN KEY (courseUnitID) REFERENCES Course_units(courseUnitID) ON DELETE CASCADE
);");
$db->exec("SET FOREIGN_KEY_CHECKS = 1;");
echo "DB Fixed!";
