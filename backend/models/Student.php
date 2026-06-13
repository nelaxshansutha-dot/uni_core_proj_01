<?php
require_once __DIR__ . '/../config/Database.php';

class Student {
    private $conn;
    private $table = "Student";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

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
    
    public function getProfile($user_id) {
        $query = "SELECT s.*, u.email, u.enrollment_no, u.role, u.fname, u.lname, u.phoneNum FROM " . $this->table . " s JOIN Users u ON s.userID = u.userID WHERE s.userID = :userID LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userID', $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
