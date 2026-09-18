<?php
require __DIR__ . '/../backend/vendor/autoload.php';

$decoded = new stdClass();
$decoded->userID = 57;
$decoded->role = 'course_representative';

$db = \Config\Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT cr.courseID, s.std_year, cr.enrollmentNo FROM course_representative cr JOIN student s ON cr.enrollmentNo = s.enrollmentNo WHERE cr.userID = :uid");
$stmt->execute([':uid' => $decoded->userID]);
$rep = $stmt->fetch(\PDO::FETCH_ASSOC);

$filters = [];
if ($rep) {
    $parts = explode('/', $rep['enrollmentNo']);
    if (count($parts) >= 2) {
        $filters['courseCode'] = $parts[1];
    }
    $batchYearStr = \Models\Student::extractBatchYear($rep['enrollmentNo']);
    if ($batchYearStr !== null) {
        $batchYear = (int)$batchYearStr;
        // 24 -> 1st year, 23 -> 2nd year, 22 -> 3rd year, 21 -> 4th year
        $calculatedYear = 24 - $batchYear + 1;
        if ($calculatedYear < 1) $calculatedYear = 1;
        if ($calculatedYear > 4) $calculatedYear = 4;
        $filters['academicYear'] = $calculatedYear;
    } else {
        $filters['academicYear'] = $rep['std_year'] ?: 1;
    }
}

print_r($filters);

$model = new \Models\Notes();
print_r($model->view(null, $filters));
