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

    public function getGroupedRequestsByCourse($course_code) {
        $query = "SELECT topic, course_code, status, COUNT(*) as request_count 
                  FROM " . $this->table . " 
                  WHERE course_code = :course_code 
                  GROUP BY topic, course_code, status 
                  ORDER BY request_count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':course_code', $course_code);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStudentRequests($student_id) {
        $query = "SELECT p.*, r.enrollmentNo as rep_enrollment FROM " . $this->table . " p 
                  LEFT JOIN users u_rep ON p.rep_id = u_rep.id 
                  LEFT JOIN Student r ON u_rep.id = r.userID
                  WHERE p.student_id = :student_id ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatusByTopic($topic, $course_code, $status, $rep_id) {
        $query = "UPDATE " . $this->table . " SET status = :status, rep_id = :rep_id WHERE topic = :topic AND course_code = :course_code";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':rep_id', $rep_id);
        $stmt->bindParam(':topic', $topic);
        $stmt->bindParam(':course_code', $course_code);
        return $stmt->execute();
    }
}
?>
