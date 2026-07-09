<?php
require_once __DIR__ . '/../config/Database.php';

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    public const ROLE_ADMIN = 'admin';
    public const ROLE_STUDENT = 'student';
    public const ROLE_REP = 'rep';
    public const ROLE_STAFF = 'staff';

    protected function getTableName() {
        return "Users";
    }

    // Encapsulation: Define the primary key internally
    protected function getPrimaryKey() {
        return "userID";
    }

    public function __construct() {
        parent::__construct();
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

    // Polymorphism: Overriding the parent's findById method to use userID column instead of id
    public function findById($id)//profile page
     {
        return parent::findByIdBase($id, 'userID');
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

    public function updateProfile($id, $data) {
        $query = "UPDATE " . $this->table . " SET fname = :fname, lname = :lname, email = :email WHERE userID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fname', $data['first_name']);
        $stmt->bindParam(':lname', $data['last_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // --- Admin specific methods below ---

    public function countAll() {
        return $this->conn->query("SELECT COUNT(*) FROM " . $this->table)->fetchColumn();
    }

    public function countVerified() {
        return $this->conn->query("SELECT COUNT(*) FROM " . $this->table . " WHERE is_verified = 1")->fetchColumn();
    }

    public function countReps() {
        return $this->conn->query("SELECT COUNT(*) FROM " . $this->table . " WHERE role = 'rep'")->fetchColumn();
    }

    public function getAllWithDetails($query, $role) {
        $sql = "SELECT u.userID as id, 
                       s.enrollmentNo as enrollment_no, 
                       st.staffID as staff_id,
                       u.email, u.phoneNum as phone_number, u.role, u.is_verified, 
                       u.is_active, u.created_at, 
                       u.fname as first_name, u.lname as last_name,
                       s.courseID as course, s.std_year as year,
                       st.dept as department
                FROM Users u
                LEFT JOIN Student s ON u.userID = s.userID
                LEFT JOIN Staff st ON u.userID = st.userID
                WHERE 1=1";
        
        $params = [];
        if (!empty($role)) {
            $sql .= " AND u.role = :role";
            $params[':role'] = $role;
        }
        if (!empty($query)) {
            $sql .= " AND (s.enrollmentNo LIKE :q OR u.email LIKE :q OR u.fname LIKE :q OR u.lname LIKE :q)";
            $params[':q'] = "%" . $query . "%";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$row) {
            if (isset($row['is_active'])) {
                $row['is_active'] = (int)$row['is_active'];
            }
            if (isset($row['is_verified'])) {
                $row['is_verified'] = (int)$row['is_verified'];
            }
        }
        return $results;
    }

    public function getRole($userId) {
        $stmt = $this->conn->prepare("SELECT role FROM Users WHERE userID = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    public function updateAdminProfile($realId, $role, $data) {
        $this->conn->beginTransaction();
        try {
            $phone = isset($data['phone_number']) ? $data['phone_number'] : null;
            $stmt = $this->conn->prepare("UPDATE Users SET email = ?, phoneNum = ?, fname = ?, lname = ? WHERE userID = ?");
            $stmt->execute([$data['email'], $phone, $data['first_name'], $data['last_name'], $realId]);

            if ($role === 'staff') {
                $dept = isset($data['department']) ? $data['department'] : '';
                $stmt = $this->conn->prepare("UPDATE Staff SET dept = ? WHERE userID = ?");
                $stmt->execute([$dept, $realId]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function toggleStatus($realId, $isActive) {
        $stmt = $this->conn->prepare("UPDATE Users SET is_active = ? WHERE userID = ?");
        return $stmt->execute([$isActive, $realId]);
    }

    public function searchStudents($query) {
        $q = "%" . $query . "%";
        $sql = "SELECT u.userID as id, s.enrollmentNo as enrollment_no, u.email, u.phoneNum as phone_number, u.role, u.fname as first_name, u.lname as last_name, s.courseID as course, s.std_year as year 
                FROM Users u 
                JOIN Student s ON u.userID = s.userID 
                WHERE (s.enrollmentNo LIKE :q OR u.fname LIKE :q OR u.lname LIKE :q) 
                AND u.role IN ('student', 'rep')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':q', $q);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudentDetails($userId) {
        $stmt = $this->conn->prepare("SELECT u.*, s.enrollmentNo, s.courseID FROM Users u JOIN Student s ON u.userID = s.userID WHERE u.userID = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
