<?php
require_once __DIR__ . '/BaseModel.php';

// Inheritance: Course inherits database connection and features from BaseModel
class Course extends BaseModel {

    // Encapsulation: Define the table name internally
    protected function getTableName() {
        return "Course_units";
    }

    // Encapsulation: Define the primary key internally
    protected function getPrimaryKey() {
        return "courseUnitID";
    }

    public function __construct() {
        parent::__construct();
    }

    // Abstraction: Implement abstract create method from BaseModel
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (courseUnitID, courseID, courseUnitName, academicYear, semester) VALUES (:courseUnitID, :courseID, :courseUnitName, :academicYear, :semester)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':courseUnitID', $data['courseUnitID']);
        $stmt->bindParam(':courseID', $data['courseID']);
        $stmt->bindParam(':courseUnitName', $data['courseUnitName']);
        $stmt->bindParam(':academicYear', $data['academicYear']);
        $stmt->bindParam(':semester', $data['semester']);
        return $stmt->execute();
    }

    

    public function getModulesByCourseYearSemester($courseID, $year, $semester) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE courseID = :courseID AND academicYear = :year AND semester = :semester";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':courseID', $courseID);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':semester', $semester);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
