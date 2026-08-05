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

    public function __construct(array $data = []) {
        $this->conn = Database::getInstance()->getConnection();
        if (!empty($data)) {
            $this->userID = $data['userID'] ?? $this->userID;
            $this->fname = $data['fname'] ?? $this->fname;
            $this->lname = $data['lname'] ?? $this->lname;
            $this->phoneNum = $data['phoneNum'] ?? $this->phoneNum;
            $this->email = $data['email'] ?? $this->email;
            $this->hash_password = $data['hash_password'] ?? $this->hash_password;
            $this->role = $data['role'] ?? $this->role;
            $this->is_active = $data['is_active'] ?? $this->is_active;
            $this->is_verified = $data['is_verified'] ?? $this->is_verified;
            $this->last_login = $data['last_login'] ?? $this->last_login;
            $this->created_at = $data['created_at'] ?? $this->created_at;
            $this->peer_learning_app_notification = $data['peer_learning_app_notification'] ?? $this->peer_learning_app_notification;
            $this->lost_item_sms_notification = $data['lost_item_sms_notification'] ?? $this->lost_item_sms_notification;
            $this->has_seen_lost_item_popup = $data['has_seen_lost_item_popup'] ?? $this->has_seen_lost_item_popup;
        }
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
        // Purge expired revoked tokens to keep the table clean
        try {
            $cleanupQuery = "DELETE FROM revoked_tokens WHERE expires_at < :now";
            $cleanupStmt = $this->conn->prepare($cleanupQuery);
            $now = time();
            $cleanupStmt->bindParam(':now', $now, PDO::PARAM_INT);
            $cleanupStmt->execute();
        } catch (\Exception $e) {
            // Log error or ignore to avoid blocking the user's logout process
        }

        $query = "INSERT INTO revoked_tokens (jti, expires_at) VALUES (:jti, :exp)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':jti', $jti);
        $stmt->bindParam(':exp', $expires_at);
        return $stmt->execute();
    }

    public function updateProfile() {
        $query = "UPDATE users SET fname = :fname, lname = :lname, phoneNum = :phoneNum WHERE userID = :uid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fname', $this->fname);
        $stmt->bindParam(':lname', $this->lname);
        $stmt->bindParam(':phoneNum', $this->phoneNum);
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
            $sql = "SELECT u.*, s.enrollmentNo, s.courseID, s.std_year, c.repID, c.rep_id_string, c.is_first_login, c.hash_password as rep_hash_password
                    FROM course_representative c
                    JOIN users u ON u.userID = c.userID
                    LEFT JOIN student s ON s.userID = c.userID
                    WHERE c.rep_id_string = :identifier OR s.enrollmentNo = :identifier";
        } elseif ($role === 'staff') {
            $sql = "SELECT u.*, st.staffID FROM users u JOIN staff st ON u.userID = st.userID WHERE st.staffID = :identifier OR u.email = :identifier";
        } elseif ($role === 'admin') {
            $sql = "SELECT u.*, a.adminID FROM users u JOIN admin a ON u.userID = a.userID WHERE a.adminID = :identifier OR u.email = :identifier";
        } else {
            return null;
        }

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':identifier', $identifier);
        $stmt->execute();
        $fullData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fullData) {
            $fullData['role'] = $role;
            switch ($role) {
                case 'admin': return new Admin($fullData);
                case 'staff': return new Staff($fullData);
                case 'course_representative':
                    if (isset($fullData['rep_hash_password']) && !empty($fullData['rep_hash_password'])) {
                        $fullData['hash_password'] = $fullData['rep_hash_password'];
                    }
                    return new CourseRepresentative($fullData);
                case 'student':
                default: return new Student($fullData);
            }
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
            switch ($role) {
                case 'admin': return new Admin($userData);
                case 'staff': return new Staff($userData);
                case 'course_representative': return new CourseRepresentative($userData);
                case 'student':
                default: return new Student($userData);
            }
        }
        return null;
    }
}
