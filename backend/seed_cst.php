<?php
require_once 'config/Database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Check if CST course exists
    $stmt = $db->prepare("SELECT courseID FROM Course WHERE courseName = 'CST'");
    $stmt->execute();
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        // Insert CST course
        $stmt = $db->prepare("INSERT INTO Course (courseName) VALUES ('CST')");
        $stmt->execute();
        $courseID = $db->lastInsertId();
        echo "Inserted CST course with ID: $courseID\n";
    } else {
        $courseID = $course['courseID'];
        echo "CST course already exists with ID: $courseID\n";
    }

    // Units to insert (Year 1, Semester 1)
    $units = [
        ['CST111-1', 'Introduction to Computer Science'],
        ['CST112-1', 'Fundamentals of Electronics'],
        ['CST113-2', 'Structured Programming'],
        ['CST114-2', 'Essential Mathematics'],
        ['CST115-1', 'English Language Level I'],
        ['CST116-2', 'Web Programming'],
        ['CST117-2', 'Fundamentals of Computer Networks'],
        ['CST118-1', 'Sinhala Language-I']
    ];

    $insertCount = 0;
    foreach ($units as $unit) {
        $courseUnitID = $unit[0];
        $name = $unit[1];

        // Check if unit exists
        $checkStmt = $db->prepare("SELECT courseUnitID FROM Course_units WHERE courseUnitID = :courseUnitID");
        $checkStmt->execute([':courseUnitID' => $courseUnitID]);
        
        if (!$checkStmt->fetch()) {
            $insertStmt = $db->prepare("INSERT INTO Course_units (courseUnitID, courseID, courseUnitName, academicYear, semester) VALUES (:courseUnitID, :courseID, :name, 1, 1)");
            $insertStmt->execute([
                ':courseUnitID' => $courseUnitID,
                ':courseID' => $courseID,
                ':name' => $name
            ]);
            $insertCount++;
            echo "Inserted unit: $name ($courseUnitID)\n";
        }
    }
    
    echo "Done. Inserted $insertCount units.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
