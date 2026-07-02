<?php
require_once __DIR__ . '/../config/Database.php';

class PeerLearning {
    private $conn;
    private $table = "peer_learning_request";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createRequest($data) {
        $query = "INSERT INTO " . $this->table . " (repID, enrollmentNo, courseCode, std_year, semester, topic) VALUES (:repID, :enrollmentNo, :courseCode, :std_year, :semester, :topic)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':repID', $data['repID']);
        $stmt->bindParam(':enrollmentNo', $data['enrollmentNo']);
        $stmt->bindParam(':courseCode', $data['courseCode']);
        $stmt->bindParam(':std_year', $data['std_year']);
        $stmt->bindParam(':semester', $data['semester']);
        $stmt->bindParam(':topic', $data['topic']);

        return $stmt->execute();
    }

    public function getGroupedRequestsByCourse($course_code) {
        $query = "SELECT topic, courseCode as course_code, status, COUNT(*) as request_count 
                  FROM " . $this->table . " 
                  WHERE courseCode = :course_code 
                  GROUP BY topic, courseCode, status 
                  ORDER BY request_count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':course_code', $course_code);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStudentRequests($enrollmentNo) {
        $query = "SELECT p.*, r.userID as rep_userID FROM " . $this->table . " p 
                  LEFT JOIN Course_representative r ON p.repID = r.repID
                  WHERE p.enrollmentNo = :enrollmentNo ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':enrollmentNo', $enrollmentNo);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatusByTopic($topic, $course_code, $status, $rep_id) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE topic = :topic AND courseCode = :course_code AND repID = :rep_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':rep_id', $rep_id);
        $stmt->bindParam(':topic', $topic);
        $stmt->bindParam(':course_code', $course_code);
        return $stmt->execute();
    }
}
?>
