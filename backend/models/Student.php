<?php
require_once __DIR__ . '/BaseModel.php';

// Inheritance: Student inherits connection and database features from BaseModel
class Student extends BaseModel {

    // Encapsulation: Define the table name internally
    protected function getTableName() {
        return "Student";
    }

    // Encapsulation: Define the primary key internally
    protected function getPrimaryKey() {
        return "enrollmentNo";
    }

    public function __construct() {
        parent::__construct();
    }

    // Abstraction: Implementing the abstract create method from BaseModel
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
        // Basic extraction of course code from enrollment number
        // Assuming format like UWU/CST/20/001 where CST maps to course ID 1
        if (stripos($enrollmentNo, '/CST/') !== false) return 1;
        if (stripos($enrollmentNo, '/IIT/') !== false) return 2;
        if (stripos($enrollmentNo, '/SCT/') !== false) return 3;
        if (stripos($enrollmentNo, '/MRT/') !== false) return 4;
        
        return null; // Return null if unable to determine
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
