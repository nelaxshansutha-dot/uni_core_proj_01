<?php
require_once __DIR__ . '/../config/Database.php';

class CourseRep {
    private $conn;
    private $table = "Course_representative";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

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
