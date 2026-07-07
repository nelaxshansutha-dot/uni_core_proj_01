<?php
require_once __DIR__ . '/BaseModel.php';

// Inheritance: CourseRep inherits database operations from BaseModel
class CourseRep extends BaseModel {

    // Encapsulation: Define the table name internally
    protected function getTableName() {
        return "Course_representative";
    }

    // Encapsulation: Define the primary key internally
    protected function getPrimaryKey() {
        return "repID";
    }

    public function __construct() {
        parent::__construct();
    }

    // Abstraction: Implement abstract create method from BaseModel
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (userID, enrollmentNo, courseID, hash_password) VALUES (:userID, :enrollmentNo, :courseID, :hash_password)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':userID', $data['userID']);
        $stmt->bindParam(':enrollmentNo', $data['enrollmentNo']);
        $stmt->bindParam(':courseID', $data['courseID']);
        $stmt->bindParam(':hash_password', $data['hash_password']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    

    public function deleteByUserId($userID) {
        $query = "DELETE FROM " . $this->table . " WHERE userID = :userID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userID', $userID);
        return $stmt->execute();
    }

    public function getRepByUserId($userID) {
        $query = "SELECT * FROM " . $this->table . " WHERE userID = :userID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userID', $userID);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- Admin specific methods below ---

    public function toggleStatus($realId, $isActive) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET is_active = ? WHERE userID = ?");
        return $stmt->execute([$isActive, $realId]);
    }

    public function assignRep($data, $student) {
        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);
        $phone = isset($data['phone']) ? $data['phone'] : null;
        $courseId = $student['courseID'];

        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("UPDATE Users SET fname = ?, lname = ?, phoneNum = ?, email = ? WHERE userID = ?");
            $stmt->execute([$data['fname'], $data['lname'], $phone, $data['email'], $data['user_id']]);

            $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (userID, enrollmentNo, courseID, hash_password, is_first_login, rep_id_string) 
                                  VALUES (?, ?, ?, ?, 1, ?)
                                  ON DUPLICATE KEY UPDATE 
                                  courseID = VALUES(courseID), 
                                  hash_password = VALUES(hash_password), 
                                  is_first_login = 1,
                                  rep_id_string = VALUES(rep_id_string)");
            $stmt->execute([$data['user_id'], $student['enrollmentNo'], $courseId, $hashed, $data['rep_id']]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
?>
