<?php
namespace Models;
use Config\Database;
use PDO;

class PeerLearningRequest {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function submit($data) {
        $query = "INSERT INTO peer_learning_request (courseUnitID, enrollmentNo, repID, std_year, courseUnitName, semester) 
                  VALUES (:cuid, :enr, :repid, :year, :name, :sem)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':cuid' => $data['courseUnitID'],
            ':enr' => $data['enrollmentNo'],
            ':repid' => $data['repID'],
            ':year' => $data['std_year'],
            ':name' => $data['courseUnitName'],
            ':sem' => $data['semester']
        ]);
    }

    public function view($requestID) {
        $stmt = $this->conn->prepare("SELECT * FROM peer_learning_request WHERE requestID = :id");
        $stmt->execute([':id' => $requestID]);
        return $stmt->fetch();
    }

    public function review($requestID, $status) {
        $stmt = $this->conn->prepare("UPDATE peer_learning_request SET status = :status WHERE requestID = :id");
        return $stmt->execute([':status' => $status, ':id' => $requestID]);
    }

    public function generateForm() {
        // Logic to generate form/pdf for the request
        return "Form generated.";
    }
}
