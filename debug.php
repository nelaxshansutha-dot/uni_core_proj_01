<?php
require 'backend/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/backend/');
$dotenv->load();
require 'backend/Config/Database.php';

$db = \Config\Database::getInstance()->getConnection();
$userID = 1; // Assuming test user is ID 1, we will try to get the first student
$stmt = $db->query("SELECT * FROM student LIMIT 1");
$student = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($student);

if ($student) {
    $courseID = $student['courseID'];
    $year = '1';
    $semester = '1';
    $stmt = $db->prepare("SELECT courseUnitID, courseUnitName FROM course_units WHERE courseID = :cid AND academicYear = :year AND semester = :sem");
    $stmt->execute([':cid' => $courseID, ':year' => $year, ':sem' => $semester]);
    $modules = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    print_r($modules);
} else {
    echo "No student found\n";
}
