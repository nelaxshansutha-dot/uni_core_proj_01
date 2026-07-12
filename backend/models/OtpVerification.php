<?php
namespace Models;
use Config\Database;
use PDO;

class OtpVerification {
    private $conn;

    // Strictly the user requested attributes
    private $otpID;
    private $otpCode;
    private $created_at;
    private $expired_at;
    private $verified_at;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Getters and Setters
    public function getOtpID() { return $this->otpID; }
    public function setOtpID($val) { $this->otpID = $val; }

    public function getOtpCode() { return $this->otpCode; }
    public function setOtpCode($val) { $this->otpCode = $val; }

    public function getCreatedAt() { return $this->created_at; }
    public function setCreatedAt($val) { $this->created_at = $val; }

    public function getExpiredAt() { return $this->expired_at; }
    public function setExpiredAt($val) { $this->expired_at = $val; }

    public function getVerifiedAt() { return $this->verified_at; }
    public function setVerifiedAt($val) { $this->verified_at = $val; }

    public function generate($userID) {
        // Hydrate requested properties
        $this->otpCode = sprintf("%06d", mt_rand(1, 999999));
        $this->expired_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $this->created_at = date('Y-m-d H:i:s');
        
        // $userID is used directly since it's not a requested class property
        $query = "INSERT INTO otp_verification (userID, otp_code, created_at, expired_at) VALUES (:uid, :otp, :cr, :exp)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':uid' => $userID, 
            ':otp' => $this->otpCode,
            ':cr' => $this->created_at,
            ':exp' => $this->expired_at
        ]);
        
        $this->otpID = $this->conn->lastInsertId();
        return $this->otpCode;
    }

    public function verify($userID, $otp) {
        $this->otpCode = $otp; // Map parameter to property
        
        $query = "SELECT * FROM otp_verification WHERE userID = :uid AND otp_code = :otp AND expired_at > NOW() AND verified_at IS NULL LIMIT 1";
        $stmt = $this->conn->prepare($query);
        // Note: $userID is not a property so it is passed directly
        $stmt->execute([':uid' => $userID, ':otp' => $this->otpCode]);
        $row = $stmt->fetch();

        if ($row) {
            // Hydrate the model
            $this->otpID = $row['otpID'];
            $this->created_at = $row['created_at'];
            $this->expired_at = $row['expired_at'];
            $this->verified_at = date('Y-m-d H:i:s');
            
            $upd = $this->conn->prepare("UPDATE otp_verification SET verified_at = :ver WHERE otpID = :id");
            $upd->execute([
                ':ver' => $this->verified_at,
                ':id' => $this->otpID
            ]);
            return true;
        }
        return false;
    }

    public function isExpired($otpID) {
        $this->otpID = $otpID; // Map parameter to property
        
        $stmt = $this->conn->prepare("SELECT expired_at FROM otp_verification WHERE otpID = :id");
        $stmt->execute([':id' => $this->otpID]);
        $res = $stmt->fetch();
        
        if ($res) {
            $this->expired_at = $res['expired_at'];
            return strtotime($this->expired_at) < time();
        }
        return true;
    }
}
