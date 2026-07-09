<?php
require_once __DIR__ . '/BaseModel.php';

class Student extends BaseModel {

    
    protected function getTableName() {
        return "Student";
    }

    
    protected function getPrimaryKey() {
        return "enrollmentNo";
    }

    public function __construct() {
        parent::__construct();
    }


    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (enrollmentNo, userID, courseID, std_year) VALUES (:enrollmentNo, :userID, :courseID, :std_year)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':enrollmentNo', $data['enrollmentNo']);
        $stmt->bindParam(':userID', $data['userID']);
        $course = isset($data['courseID']) ? $data['courseID'] : null;
        $year = isset($data['std_year']) ? $data['std_year'] : null;
        $stmt->bindParam(':courseID', $course);
        $stmt->bindParam(':std_year', $year);

        return $stmt->execute();
    }
    
    public function getProfile($user_id) {
        $query = "SELECT s.*, u.email, u.role, u.fname, u.lname, u.phoneNum FROM " . $this->table . " s JOIN Users u ON s.userID = u.userID WHERE s.userID = :userID LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userID', $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function extractCourseFromEnrollment($enrollmentNo) {
    
        $parts = explode('/', strtoupper(trim($enrollmentNo)));
        if (count($parts) < 2 || empty($parts[1])) {
            return null;
        }
        $courseCode = $parts[1]; // e.g. "CST"

        try {
            require_once __DIR__ . '/../config/Database.php';
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT courseID FROM Course WHERE courseName LIKE ? LIMIT 1");
            $stmt->execute(['%' . $courseCode . '%']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int)$row['courseID'] : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function updateAdminProfile($user_id, $enrollmentNo, $courseID, $std_year) {
        $query = "UPDATE " . $this->table . " SET enrollmentNo = :enrollmentNo, courseID = :courseID, std_year = :std_year WHERE userID = :userID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':enrollmentNo', $enrollmentNo);
        $stmt->bindParam(':courseID', $courseID);
        $stmt->bindParam(':std_year', $std_year);
        $stmt->bindParam(':userID', $user_id);
        return $stmt->execute();
    }
}
?>
