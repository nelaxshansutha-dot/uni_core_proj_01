<?php
namespace DAO;

use PDO;

class OtpVerificationDAO extends BaseDAO {
    public function generate($userID, $otpCode, $created_at, $expired_at) {
        $query = "INSERT INTO otp_verification (userID, otp_code, created_at, expired_at) VALUES (:uid, :otp, :cr, :exp)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':uid' => $userID, 
            ':otp' => $otpCode,
            ':cr' => $created_at,
            ':exp' => $expired_at
        ]);
        return $this->db->lastInsertId();
    }

    public function getActiveOtp($userID, $otpCode, $now) {
        $query = "SELECT * FROM otp_verification WHERE userID = :uid AND otp_code = :otp AND expired_at > :now AND verified_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':uid' => $userID, ':otp' => $otpCode, ':now' => $now]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function setVerified($otpID, $verified_at) {
        $upd = $this->db->prepare("UPDATE otp_verification SET verified_at = :ver WHERE otpID = :id");
        return $upd->execute([
            ':ver' => $verified_at,
            ':id' => $otpID
        ]);
    }

    public function getExpiredAt($otpID) {
        $stmt = $this->db->prepare("SELECT expired_at FROM otp_verification WHERE otpID = :id");
        $stmt->execute([':id' => $otpID]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? $res['expired_at'] : null;
    }
}
