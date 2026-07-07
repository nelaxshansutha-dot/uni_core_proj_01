<?php
require_once __DIR__ . '/BaseModel.php';

// Inheritance: PeerLearning inherits common database facilities from BaseModel
class PeerLearning extends BaseModel {

    // Encapsulation: Define the table name internally
    protected function getTableName() {
        return "peer_learning_request";
    }

    // Encapsulation: Define the primary key internally
    protected function getPrimaryKey() {
        return "requestID";
    }

    public function __construct() {
        parent::__construct();
    }

    // Abstraction: Implement abstract create method from BaseModel
    public function create($data) {
        return $this->createRequest($data);
    }

    

    public function createRequest($data) {
        $query = "INSERT INTO " . $this->table . " (repID, enrollmentNo, courseUnitID, std_year, semester, courseUnitName) VALUES (:repID, :enrollmentNo, :courseUnitID, :std_year, :semester, :courseUnitName)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':repID', $data['repID']);
        $stmt->bindParam(':enrollmentNo', $data['enrollmentNo']);
        $stmt->bindParam(':courseUnitID', $data['courseUnitID']);
        $stmt->bindParam(':std_year', $data['std_year']);
        $stmt->bindParam(':semester', $data['semester']);
        $stmt->bindParam(':courseUnitName', $data['courseUnitName']);

        return $stmt->execute();
    }

    public function getGroupedRequestsByCourse($courseUnitID) {
        $query = "SELECT courseUnitName, courseUnitID, status, COUNT(*) as request_count 
                  FROM " . $this->table . " 
                  WHERE courseUnitID = :courseUnitID
                  GROUP BY courseUnitName, courseUnitID, status 
                  ORDER BY request_count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':courseUnitID', $courseUnitID);
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

    public function updateStatusByTopic($courseUnitName, $courseUnitID, $status, $rep_id) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE courseUnitName = :courseUnitName AND courseUnitID = :courseUnitID AND repID = :rep_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':rep_id', $rep_id);
        $stmt->bindParam(':courseUnitName', $courseUnitName);
        $stmt->bindParam(':courseUnitID', $courseUnitID);
        return $stmt->execute();
    }
}
?>
