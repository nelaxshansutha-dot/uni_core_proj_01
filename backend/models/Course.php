<?php
namespace Models;
use Config\Database;
use PDO;

class Course {
    private $courseID;
    private $courseName;
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getCourseUnits() {
        $stmt = $this->conn->prepare("SELECT * FROM course_units WHERE courseID = :cid");
        $stmt->bindParam(':cid', $this->courseID);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
