<?php
require_once __DIR__ . '/BaseModel.php';

// Inheritance: Student inherits connection and database features from BaseModel
class Student extends BaseModel {

    // Encapsulation: Define the table name internally
    protected function getTableName() {
        return "Student";
    }

    public function __construct() {
        parent::__construct();
    }

    // Abstraction: Implementing the abstract create method from BaseModel
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (enrollmentNo, userID, courseID, std_year) VALUES (:enrollmentNo, :userID, :courseID, :std_year)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':enrollmentNo', $data['enrollmentNo']);
        $stmt->bindParam(':userID', $data['userID']);
        $course = isset($data['courseID']) ? $data['courseID'] : null;
        $year = isset($data['std_year']) ? $data['std_year'] : null;
        $stmt->bindParam(':courseID', $course);
        $stmt->bindParam(':std_year', $year);

        return $stmt->execute();
    }
    
    // Polymorphism: Override default findById to query using enrollmentNo
    public function findById($id) {
        return $this->findByIdBase($id, 'enrollmentNo');
    }
    
    public function getProfile($user_id) {
        $query = "SELECT s.*, u.email, u.role, u.fname, u.lname, u.phoneNum FROM " . $this->table . " s JOIN Users u ON s.userID = u.userID WHERE s.userID = :userID LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userID', $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
