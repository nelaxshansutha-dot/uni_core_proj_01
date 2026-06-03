<?php
require_once __DIR__ . '/../config/Database.php';

class PeerLearning {
    private $conn;
    private $table = "peer_learning_requests";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createRequest($data) {
        $query = "INSERT INTO " . $this->table . " (student_id, course_code, topic, description) VALUES (:student_id, :course_code, :topic, :description)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':student_id', $data['student_id']);
        $stmt->bindParam(':course_code', $data['course_code']);
        $stmt->bindParam(':topic', $data['topic']);
        $stmt->bindParam(':description', $data['description']);

        return $stmt->execute();
    }

    public function assignRep($request_id, $rep_id) {
        $query = "UPDATE " . $this->table . " SET rep_id = :rep_id WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rep_id', $rep_id);
        $stmt->bindParam(':id', $request_id);
        return $stmt->execute();
    }
    
    public function updateStatus($request_id, $status, $rep_id) {
        // Only the assigned rep can update status
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id AND rep_id = :rep_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $request_id);
        $stmt->bindParam(':rep_id', $rep_id);
        return $stmt->execute();
    }

    public function getRequestsByCourse($course_code) {
        $query = "SELECT p.*, u.enrollment_no as student_enrollment FROM " . $this->table . " p 
                  JOIN users u ON p.student_id = u.id 
                  WHERE p.course_code = :course_code ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':course_code', $course_code);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStudentRequests($student_id) {
        $query = "SELECT p.*, r.enrollment_no as rep_enrollment FROM " . $this->table . " p 
                  LEFT JOIN users r ON p.rep_id = r.id 
                  WHERE p.student_id = :student_id ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
