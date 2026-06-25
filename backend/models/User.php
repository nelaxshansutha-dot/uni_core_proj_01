<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $conn;
    private $table = "Users";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function findByEnrollment($enrollment_no)
    {
        $query = "SELECT u.*, s.enrollmentNo as std_enrollment, cr.enrollmentNo as rep_enrollment, st.staffID as staff_enrollment
                  FROM " . $this->table . " u
                  LEFT JOIN Student s ON u.userID = s.userID
                  LEFT JOIN Course_representative cr ON u.userID = cr.userID
                  LEFT JOIN Staff st ON u.userID = st.userID
                  WHERE s.enrollmentNo = :id 
                     OR cr.enrollmentNo = :id 
                     OR cr.rep_id_string = :id
                     OR st.staffID = :id
                     OR u.email = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $enrollment_no);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['enrollment_no'] = $result['std_enrollment'] ?? $result['rep_enrollment'] ?? $result['staff_enrollment'] ?? null;
            return $result;
        }
        return false;
    }

    public function findByEmail($email)// forgot password
     {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id)//profile page
     {
        $query = "SELECT * FROM " . $this->table . " WHERE userID = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) //new user insert
    
    {
        $query = "INSERT INTO " . $this->table . " (fname, lname, email, phoneNum, hash_password, role, is_verified) 
                  VALUES (:fname, :lname, :email, :phoneNum, :hash_password, :role, FALSE)";
        $stmt = $this->conn->prepare($query);

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

    public function markAsVerified($id) // login
    
    {
        $query = "UPDATE " . $this->table . " SET is_verified = TRUE WHERE userID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    public function updatePassword($id, $new_hash) //forgot password/changepassword onnly hashpassword
    
    {
        $query = "UPDATE " . $this->table . " SET hash_password = :hash WHERE userID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hash', $new_hash);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updateLoginTime($id) //Admin dashboard- last active time
    
    {
        $query = "UPDATE " . $this->table . " SET last_login = CURRENT_TIMESTAMP WHERE userID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updateProfileFields($id, $phoneNum) //upadte phone number in profile
    
    {
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
