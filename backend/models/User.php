<?php

namespace Models;

use Config\Database;
use PDO;
use Firebase\JWT\JWT;

abstract class User {
    protected $conn;

   
    protected $userID;
    protected $fname;
    protected $lname;
    protected $phoneNum;
    protected $email;
    protected $hash_password;
    protected $role;
    protected $is_active;
    protected $is_verified;
    protected $last_login;
    protected $created_at;
    protected $peer_learning_app_notification;
    protected $lost_item_sms_notification;
    protected $has_seen_lost_item_popup;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
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

    
    public function hydrate(array $data) {
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
        return $this;
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
        // Issue token / email logic
    }

    public function verifyOTP($otpCode) {
        $query = "SELECT * FROM otp_verification WHERE userID = :uid AND otp_code = :otp AND expired_at > NOW() AND verified_at IS NULL LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $this->userID);
        $stmt->bindParam(':otp', $otpCode);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row) {
            $upd = $this->conn->prepare("UPDATE otp_verification SET verified_at = NOW() WHERE otpID = :id");
            $upd->bindParam(':id', $row['otpID']);
            $upd->execute();

            $updUser = $this->conn->prepare("UPDATE users SET is_verified = 1 WHERE userID = :uid");
            $updUser->bindParam(':uid', $this->userID);
            $updUser->execute();

            return true;
        }
        return false;
    }
}
