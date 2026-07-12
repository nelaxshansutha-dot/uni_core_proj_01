<?php
namespace Models;
use Config\Database;
use PDO;

class CourseUnit {
    private $courseUnitID;
    private $courseID;
    private $courseUniName;
    private $academicYear;
    private $semester;
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getRequest() {
        $stmt = $this->conn->prepare("SELECT * FROM peer_learning_request WHERE courseUnitID = :cuid");
        $stmt->bindParam(':cuid', $this->courseUnitID);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
