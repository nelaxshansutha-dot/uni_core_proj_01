<?php
require_once __DIR__ . '/../config/Database.php';

class Course {
    private $conn;
    private $table = "Course_units";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getModulesByCourseYearSemester($courseID, $year, $semester) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE courseID = :courseID AND year = :year AND semester = :semester";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':courseID', $courseID);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':semester', $semester);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
