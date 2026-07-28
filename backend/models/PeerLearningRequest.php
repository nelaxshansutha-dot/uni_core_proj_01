<?php
namespace Models;
use Config\Database;
use PDO;

class PeerLearningRequest {
    private $conn;

    private $requestID;
    private $repID;
    private $enrollmentNo;
    private $topic;
    private $status;
    private $created_at;

    public function __construct(array $data = []) {
        $this->conn = Database::getInstance()->getConnection();
        if (!empty($data)) {
            $this->requestID = $data['requestID'] ?? null;
            $this->repID = $data['repID'] ?? null;
            $this->enrollmentNo = $data['enrollmentNo'] ?? $data['enrolllmentNo'] ?? null;
            $this->topic = $data['topic'] ?? null;
            $this->status = $data['status'] ?? null;
            $this->created_at = $data['created_at'] ?? null;
        }
    }

    public function getRequestID() { return $this->requestID; }
    public function setRequestID($val) { $this->requestID = $val; return $this; }

    public function getRepID() { return $this->repID; }
    public function setRepID($val) { $this->repID = $val; return $this; }

    public function getEnrollmentNo() { return $this->enrollmentNo; }
    public function setEnrollmentNo($val) { $this->enrollmentNo = $val; return $this; }

    public function getTopic() { return $this->topic; }
    public function setTopic($val) { $this->topic = $val; return $this; }

    public function getStatus() { return $this->status; }
    public function setStatus($val) { $this->status = $val; return $this; }

    public function getCreatedAt() { return $this->created_at; }
    public function setCreatedAt($val) { $this->created_at = $val; return $this; }

    public function submit($data) {
        $query = "INSERT INTO peer_learning_request (courseUnitID, enrollmentNo, repID, std_year, courseUnitName, semester, description) 
                  VALUES (:cuid, :enr, :repid, :year, :name, :sem, :description)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':cuid' => $data['courseUnitID'] ?? null,
            ':enr' => $this->enrollmentNo ?? ($data['enrollmentNo'] ?? null),
            ':repid' => $this->repID ?? ($data['repID'] ?? null),
            ':year' => $data['std_year'] ?? null,
            ':name' => $data['courseUnitName'] ?? null,
            ':sem' => $data['semester'] ?? null,
            ':description' => $data['description'] ?? null
        ]);
    }

    public function view($requestID = null) {
        if ($requestID) {
            $stmt = $this->conn->prepare("SELECT * FROM peer_learning_request WHERE requestID = :id");
            $stmt->execute([':id' => $requestID]);
            return $stmt->fetch();
        }
        return false;
    }

    public function review($requestID, $status) {
        $stmt = $this->conn->prepare("UPDATE peer_learning_request SET status = :status WHERE requestID = :id");
        return $stmt->execute([':status' => $status, ':id' => $requestID]);
    }

    public function generateForm() {
        
        return "Form generated.";
    }
}
