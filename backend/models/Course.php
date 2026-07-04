<?php
require_once __DIR__ . '/BaseModel.php';

// Inheritance: Course inherits database connection and features from BaseModel
class Course extends BaseModel {

    // Encapsulation: Define the table name internally
    protected function getTableName() {
        return "Course_units";
    }

    public function __construct() {
        parent::__construct();
    }

    // Abstraction: Implement abstract create method from BaseModel
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (courseUnitID, courseID, name, year, semester) VALUES (:courseUnitID, :courseID, :name, :year, :semester)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':courseUnitID', $data['courseUnitID']);
        $stmt->bindParam(':courseID', $data['courseID']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':year', $data['year']);
        $stmt->bindParam(':semester', $data['semester']);
        return $stmt->execute();
    }

    // Polymorphism: Override default findById to query using courseUnitID
    public function findById($id) {
        return $this->findByIdBase($id, 'courseUnitID');
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
