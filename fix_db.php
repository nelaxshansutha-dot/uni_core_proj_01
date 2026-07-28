<?php
require 'backend/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/backend/');
$dotenv->load();
require 'backend/Config/Database.php';

$db = \Config\Database::getInstance()->getConnection();

$db->exec("SET FOREIGN_KEY_CHECKS = 0;");
$db->exec("DROP TABLE IF EXISTS course_units;");
$db->exec("CREATE TABLE Course_units (
    courseUnitID VARCHAR(20) PRIMARY KEY,
    courseID INT NOT NULL,
    courseUnitName VARCHAR(100) NOT NULL,
    academicYear INT NOT NULL,
    semester INT NOT NULL,
    FOREIGN KEY (courseID) REFERENCES Course(courseID) ON DELETE CASCADE
);");
$db->exec("INSERT IGNORE INTO Course (courseID, courseName) VALUES (1, 'Computer Science and Technology');");
$db->exec("INSERT INTO Course_units (courseUnitID, courseID, courseUnitName, academicYear, semester) VALUES
('CST 102-2', 1, 'Introduction to Computer Science', 1, 1),
('CST 101-2', 1, 'Fundamentals of Electronics', 1, 1),
('CST 121-3', 1, 'Structured Programming', 1, 1),
('CST 111-2', 1, 'Essential Mathematics', 1, 1),
('ESD 121-2', 1, 'English Language Level I', 1, 1),
('CST 122-2', 1, 'Web Programming', 1, 1),
('CST 131-2', 1, 'Fundamentals of Computer Networks', 1, 1);");
$db->exec("UPDATE student SET courseID=1 WHERE courseID IS NULL;");
$db->exec("SET FOREIGN_KEY_CHECKS = 1;");
echo "DB Fixed and Data Inserted!";
