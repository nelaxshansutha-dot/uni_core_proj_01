<?php
require_once __DIR__ . '/../config/Database.php';

class Student {
    private $conn;
    private $table = "students";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (user_id, first_name, last_name, course, year) VALUES (:user_id, :first_name, :last_name, :course, :year)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':course', $data['course']);
        $stmt->bindParam(':year', $data['year']);

        return $stmt->execute();
    }
    
    public function getProfile($user_id) {
        $query = "SELECT s.*, u.email, u.enrollment_no, u.role FROM " . $this->table . " s JOIN users u ON s.user_id = u.id WHERE s.user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
