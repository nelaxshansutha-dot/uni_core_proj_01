<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $conn;
    private $table = "Users";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function findByEnrollment($enrollment_no) {
        $query = "SELECT * FROM " . $this->table . " WHERE enrollment_no = :id OR staff_id = :id OR rep_id = :id OR email = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $enrollment_no);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE userID = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (enrollment_no, staff_id, rep_id, fname, lname, email, phoneNum, hash_password, role, is_verified) 
                  VALUES (:enrollment_no, :staff_id, :rep_id, :fname, :lname, :email, :phoneNum, :hash_password, :role, FALSE)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':enrollment_no', $data['enrollment_no']);
        $stmt->bindParam(':staff_id', $data['staff_id']);
        $stmt->bindParam(':rep_id', $data['rep_id']);
        $stmt->bindParam(':fname', $data['fname']);
        $stmt->bindParam(':lname', $data['lname']);
        $stmt->bindParam(':email', $data['email']);
        $phone = isset($data['phoneNum']) ? $data['phoneNum'] : null;
        $stmt->bindParam(':phoneNum', $phone);
        $stmt->bindParam(':hash_password', $data['hash_password']);
        $stmt->bindParam(':role', $data['role']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function markAsVerified($id) {
        $query = "UPDATE " . $this->table . " SET is_verified = TRUE WHERE userID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    public function updatePassword($id, $new_hash) {
        $query = "UPDATE " . $this->table . " SET hash_password = :hash WHERE userID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hash', $new_hash);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updateLoginTime($id) {
        $query = "UPDATE " . $this->table . " SET last_login = CURRENT_TIMESTAMP WHERE userID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updateProfileFields($id, $phoneNum) {
        $query = "UPDATE " . $this->table . " SET phoneNum = :phone WHERE userID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':phone', $phoneNum);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateNotificationSettings($id, $smsPref, $popupSeen) {
        $query = "UPDATE " . $this->table . " SET lost_item_sms_notification = :smsPref, has_seen_lost_item_popup = :popupSeen WHERE userID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':smsPref', $smsPref, PDO::PARAM_INT);
        $stmt->bindParam(':popupSeen', $popupSeen, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>
