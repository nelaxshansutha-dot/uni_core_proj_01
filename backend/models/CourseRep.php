<?php
require_once __DIR__ . '/BaseModel.php';

// Inheritance: CourseRep inherits database operations from BaseModel
class CourseRep extends BaseModel {

    // Encapsulation: Define the table name internally
    protected function getTableName() {
        return "Course_representative";
    }

    public function __construct() {
        parent::__construct();
    }

    // Abstraction: Implement abstract create method from BaseModel
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (userID, enrollmentNo, courseID, hash_password) VALUES (:userID, :enrollmentNo, :courseID, :hash_password)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':userID', $data['userID']);
        $stmt->bindParam(':enrollmentNo', $data['enrollmentNo']);
        $stmt->bindParam(':courseID', $data['courseID']);
        $stmt->bindParam(':hash_password', $data['hash_password']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Polymorphism: Override findById to query using repID
    public function findById($id) {
        return $this->findByIdBase($id, 'repID');
    }

    public function deleteByUserId($userID) {
        $query = "DELETE FROM " . $this->table . " WHERE userID = :userID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userID', $userID);
        return $stmt->execute();
    }

    public function getRepByUserId($userID) {
        $query = "SELECT * FROM " . $this->table . " WHERE userID = :userID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userID', $userID);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
