<?php

namespace Models;

use Config\Database;
use PDO;
use Firebase\JWT\JWT;

abstract class User {
    protected $conn;

   
    private $userID;
    private $fname;
    private $lname;
    private $phoneNum;
    private $email;
    private $hash_password;
    private $role;
    private $is_active;
    private $is_verified;
    private $last_login;
    private $created_at;
    private $peer_learning_app_notification;
    private $lost_item_sms_notification;
    private $has_seen_lost_item_popup;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function hydrate(array $data = []): static {
        if (array_key_exists('userID', $data)) {
            $this->setUserID($data['userID']);
        }
        if (array_key_exists('fname', $data)) {
            $this->setFname($data['fname']);
        }
        if (array_key_exists('lname', $data)) {
            $this->setLname($data['lname']);
        }
        if (array_key_exists('phoneNum', $data)) {
            $this->setPhoneNum($data['phoneNum']);
        }
        if (array_key_exists('email', $data)) {
            $this->setEmail($data['email']);
        }
        if (array_key_exists('hash_password', $data)) {
            $this->setHashPassword($data['hash_password']);
        }
        if (array_key_exists('role', $data)) {
            $this->setRole($data['role']);
        }
        if (array_key_exists('is_active', $data)) {
            $this->setIsActive($data['is_active']);
        }
        if (array_key_exists('is_verified', $data)) {
            $this->setIsVerified($data['is_verified']);
        }
        if (array_key_exists('last_login', $data)) {
            $this->setLastLogin($data['last_login']);
        }
        if (array_key_exists('created_at', $data)) {
            $this->setCreatedAt($data['created_at']);
        }
        if (array_key_exists('peer_learning_app_notification', $data)) {
            $this->setPeerLearningAppNotification($data['peer_learning_app_notification']);
        }
        if (array_key_exists('lost_item_sms_notification', $data)) {
            $this->setLostItemSmsNotification($data['lost_item_sms_notification']);
        }
        if (array_key_exists('has_seen_lost_item_popup', $data)) {
            $this->setHasSeenLostItemPopup($data['has_seen_lost_item_popup']);
        }
        
        return $this;
    }

    public function hydrateFromRequest(array $data = []): static {
        if (array_key_exists('fname', $data)) {
            $this->setFname($data['fname']);
        } elseif (array_key_exists('first_name', $data)) {
            $this->setFname($data['first_name']);
        }

        if (array_key_exists('lname', $data)) {
            $this->setLname($data['lname']);
        } elseif (array_key_exists('last_name', $data)) {
            $this->setLname($data['last_name']);
        }

        if (array_key_exists('phoneNum', $data)) {
            $this->setPhoneNum($data['phoneNum']);
        } elseif (array_key_exists('phone_number', $data)) {
            $this->setPhoneNum($data['phone_number']);
        }

        if (array_key_exists('email', $data)) {
            $this->setEmail($data['email']);
        }

        if (array_key_exists('hash_password', $data)) {
            $this->setHashPassword($data['hash_password']);
        } elseif (array_key_exists('password', $data)) {
            $this->setHashPassword($data['password']);
        }

        if (array_key_exists('peer_learning_app_notification', $data)) {
            $this->setPeerLearningAppNotification($data['peer_learning_app_notification']);
        }
        if (array_key_exists('lost_item_sms_notification', $data)) {
            $this->setLostItemSmsNotification($data['lost_item_sms_notification']);
        }
        if (array_key_exists('has_seen_lost_item_popup', $data)) {
            $this->setHasSeenLostItemPopup($data['has_seen_lost_item_popup']);
        }

        return $this;
    }

    
    public function getUserID() { return $this->userID; }
    public function setUserID($val) { $this->userID = $val; return $this; }
    
    public function getFname() { return $this->fname; }
    public function setFname($val) { $this->fname = $val; return $this; }

    public function getLname() { return $this->lname; }
    public function setLname($val) { $this->lname = $val; return $this; }

    public function getPhoneNum() { return $this->phoneNum; }
    public function setPhoneNum($val) { $this->phoneNum = $val; return $this; }

    public function getEmail() { return $this->email; }
    public function setEmail($val) { $this->email = $val; return $this; }

    public function getHashPassword() { return $this->hash_password; }
    public function setHashPassword($val) { $this->hash_password = $val; return $this; }

    public function getRole() { return $this->role; }
    public function setRole($val) { $this->role = $val; return $this; }

    public function getIsActive() { return $this->is_active; }
    public function setIsActive($val) { $this->is_active = $val; return $this; }

    public function getIsVerified() { return $this->is_verified; }
    public function setIsVerified($val) { $this->is_verified = $val; return $this; }

    public function getLastLogin() { return $this->last_login; }
    public function setLastLogin($val) { $this->last_login = $val; return $this; }

    public function getCreatedAt() { return $this->created_at; }
    public function setCreatedAt($val) { $this->created_at = $val; return $this; }

    public function getPeerLearningAppNotification() { return $this->peer_learning_app_notification; }
    public function setPeerLearningAppNotification($val) { $this->peer_learning_app_notification = $val; return $this; }

    public function getLostItemSmsNotification() { return $this->lost_item_sms_notification; }
    public function setLostItemSmsNotification($val) { $this->lost_item_sms_notification = $val; return $this; }

    public function getHasSeenLostItemPopup() { return $this->has_seen_lost_item_popup; }
    public function setHasSeenLostItemPopup($val) { $this->has_seen_lost_item_popup = $val; return $this; }

    public static function createInstanceFromRole(string $role, array $data = []) {
        switch ($role) {
            case 'admin':
                $user = new Admin();
                break;
            case 'staff':
                $user = new Staff();
                break;
            case 'course_representative':
                $user = new CourseRepresentative();
                if (isset($data['rep_hash_password']) && !empty($data['rep_hash_password'])) {
                    $data['hash_password'] = $data['rep_hash_password'];
                }
                break;
            case 'student':
            default:
                $user = new Student();
                break;
        }
        $user->hydrate($data);
        return $user;
    }

    public function register() {
        $query = "INSERT INTO users (fname, lname, email, phoneNum, hash_password, role) 
                  VALUES (:fname, :lname, :email, :phoneNum, :hash, :role)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fname', $this->fname);
        $stmt->bindParam(':lname', $this->lname);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phoneNum', $this->phoneNum);
        $stmt->bindParam(':hash', $this->hash_password);
        $stmt->bindParam(':role', $this->role);

        if ($stmt->execute()) {
            $this->userID = $this->conn->lastInsertId();
            return $this->userID;
        }
        
        throw new \Exception("Database insert into users failed: " . implode(" ", $stmt->errorInfo()));
    }

    public function login($password) {
        if ($this->hash_password && password_verify($password, $this->hash_password)) {
            if (!$this->is_active) {
                throw new \Exception("Account is deactivated.");
            }
            if (!$this->is_verified) {
                throw new \Exception("Account is not verified.");
            }
            
            // update last login
            $upd = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE userID = :uid");
            $upd->bindParam(':uid', $this->userID);
            $upd->execute();
            return true;
        }
        return false;
    }

    public function logout($jti, $expires_at) {
       
        try {
            $cleanupQuery = "DELETE FROM revoked_tokens WHERE expires_at < :now";
            $cleanupStmt = $this->conn->prepare($cleanupQuery);
            $now = time();
            $cleanupStmt->bindParam(':now', $now, PDO::PARAM_INT);
            $cleanupStmt->execute();
        } catch (\Exception $e) {
         
        }

        $query = "INSERT INTO revoked_tokens (jti, expires_at) VALUES (:jti, :exp)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':jti', $jti);
        $stmt->bindParam(':exp', $expires_at);
        return $stmt->execute();
    }

    public function updateProfile() {
        $query = "UPDATE users SET fname = :fname, lname = :lname, phoneNum = :phoneNum, lost_item_sms_notification = :smsPref, peer_learning_app_notification = :peerPref";
        if ($this->hash_password) {
            $query .= ", hash_password = :hash";
        }
        $query .= " WHERE userID = :uid";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fname', $this->fname);
        $stmt->bindParam(':lname', $this->lname);
        $stmt->bindParam(':phoneNum', $this->phoneNum);
        $stmt->bindParam(':smsPref', $this->lost_item_sms_notification);
        $stmt->bindParam(':peerPref', $this->peer_learning_app_notification);
        if ($this->hash_password) {
            $stmt->bindParam(':hash', $this->hash_password);
        }
        $stmt->bindParam(':uid', $this->userID);
        return $stmt->execute();
    }

    public function changePassword($newHash) {
        $query = "UPDATE users SET hash_password = :hash WHERE userID = :uid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hash', $newHash);
        $stmt->bindParam(':uid', $this->userID);
        return $stmt->execute();
    }

    public function forgotPassword() {
        if (!$this->userID || !$this->email) {
            return false;
        }
        $otpModel = new OtpVerification();
        $otp = $otpModel->generate($this->userID);
        return \Utils\MailService::sendOTP($this->email, $otp);
    }

    public function verifyOTP($otpCode) {
        $now = date('Y-m-d H:i:s');
        $query = "SELECT * FROM otp_verification WHERE userID = :uid AND otp_code = :otp AND expired_at > :now AND verified_at IS NULL LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $this->userID);
        $stmt->bindParam(':otp', $otpCode);
        $stmt->bindParam(':now', $now);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row) {
            $upd = $this->conn->prepare("UPDATE otp_verification SET verified_at = :now WHERE otpID = :id");
            $upd->bindParam(':now', $now);
            $upd->bindParam(':id', $row['otpID']);
            $upd->execute();

            $updUser = $this->conn->prepare("UPDATE users SET is_verified = 1 WHERE userID = :uid");
            $updUser->bindParam(':uid', $this->userID);
            $updUser->execute();

            return true;
        }
        return false;
    }

    public static function loadByIdentifier(string $identifier, string $role) {
        $db = Database::getInstance()->getConnection();
        
        if ($role === 'student') {
            $sql = "SELECT u.*, s.enrollmentNo, s.courseID, s.std_year 
                    FROM users u 
                    JOIN student s ON u.userID = s.userID 
                    WHERE s.enrollmentNo = :identifier";
        } elseif ($role === 'course_representative') {
            // Rep can login using their rep_id_string (e.g. rep_uwu/cst/23/088)
            // The users table role may still be 'student' — dual-login is supported
            // Note: PDO does not support the same named param twice — use :identifier1 and :identifier2
            $sql = "SELECT u.*, s.enrollmentNo, s.courseID, s.std_year, c.repID, c.rep_id_string, c.is_first_login, c.hash_password as rep_hash_password
                    FROM course_representative c
                    JOIN users u ON u.userID = c.userID
                    LEFT JOIN student s ON s.userID = c.userID
                    WHERE c.rep_id_string = :identifier1 OR s.enrollmentNo = :identifier2";
        } elseif ($role === 'staff') {
            // Note: PDO does not support the same named param twice — use :identifier1 and :identifier2
            $sql = "SELECT u.*, st.staffID FROM users u JOIN staff st ON u.userID = st.userID WHERE st.staffID = :identifier1 OR u.email = :identifier2";
        } elseif ($role === 'admin') {
            // Note: PDO does not support the same named param twice — use :identifier1 and :identifier2
            $sql = "SELECT u.*, a.adminID FROM users u JOIN admin a ON u.userID = a.userID WHERE a.adminID = :identifier1 OR u.email = :identifier2";
        } else {
            return null;
        }

        $stmt = $db->prepare($sql);
        if (in_array($role, ['course_representative', 'staff', 'admin'])) {
            // These queries use :identifier twice — PDO requires separate named params
            $stmt->bindParam(':identifier1', $identifier);
            $stmt->bindParam(':identifier2', $identifier);
        } else {
            $stmt->bindParam(':identifier', $identifier);
        }
        $stmt->execute();
        $fullData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fullData) {
            $fullData['role'] = $role;
            return self::createInstanceFromRole($role, $fullData);
        }
        return null;
    }

    public static function loadByEmail(string $email) {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $role = $userData['role'];
            return self::createInstanceFromRole($role, $userData);
        }
        return null;
    }
}
