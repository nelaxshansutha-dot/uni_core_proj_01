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

 
    public function __construct(array $data = []) {
        $this->conn = Database::getInstance()->getConnection();
        
        if (!empty($data)) {
            $this->courseUnitID = $data['courseUnitID'] ?? $this->courseUnitID;
            $this->courseID = $data['courseID'] ?? $this->courseID;
            $this->courseUniName = $data['courseUniName'] ?? $data['name'] ?? $this->courseUniName;
            $this->academicYear = $data['academicYear'] ?? $data['year'] ?? $this->academicYear;
            $this->semester = $data['semester'] ?? $this->semester;
        }
    }

    public function getCourseUnitID() { return $this->courseUnitID; }
    public function setCourseUnitID($val) { $this->courseUnitID = $val; return $this; }

    public function getCourseID() { return $this->courseID; }
    public function setCourseID($val) { $this->courseID = $val; return $this; }

    public function getCourseUniName() { return $this->courseUniName; }
    public function setCourseUniName($val) { $this->courseUniName = $val; return $this; }

    public function getAcademicYear() { return $this->academicYear; }
    public function setAcademicYear($val) { $this->academicYear = $val; return $this; }

    public function getSemester() { return $this->semester; }
    public function setSemester($val) { $this->semester = $val; return $this; }

   
    public function create() {
        try {
            $query = "INSERT INTO course_units (courseUnitID, courseID, courseUnitName, semester) 
                      VALUES (:cuid, :cid, :name, :sem)";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':cuid' => $this->courseUnitID,
                ':cid' => $this->courseID,
                ':name' => $this->courseUniName,
                ':sem' => $this->semester
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Failed to create course unit: " . $e->getMessage());
        }
    }

  
    public function view($courseUnitID = null) {
        if ($courseUnitID) {
            $stmt = $this->conn->prepare("SELECT * FROM course_units WHERE courseUnitID = :cuid");
            $stmt->execute([':cuid' => $courseUnitID]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM course_units ORDER BY semester ASC, courseUnitName ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

   
    public function getRequest() {
        if (empty($this->courseUnitID)) {
            throw new \Exception("CourseUnitID must be set before fetching requests.");
        }
        
        $stmt = $this->conn->prepare("SELECT * FROM peer_learning_request WHERE courseUnitID = :cuid");
        $stmt->bindValue(':cuid', $this->courseUnitID, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
