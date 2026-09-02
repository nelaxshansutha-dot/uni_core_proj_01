<?php
namespace Models;
use Config\Database;
class OtpVerification {
    private $otpID;
    private $otpCode;
    private $created_at;
    private $expired_at;
    private $verified_at;
    private $dao;

    public function __construct() {
        $this->dao = new \DAO\OtpVerificationDAO();
    }

   
    public function getOtpID() { return $this->otpID; }
    public function setOtpID($val) { $this->otpID = $val; return $this; }

    public function getOtpCode() { return $this->otpCode; }
    public function setOtpCode($val) { $this->otpCode = $val; return $this; }

    public function getCreatedAt() { return $this->created_at; }
    public function setCreatedAt($val) { $this->created_at = $val; return $this; }

    public function getExpiredAt() { return $this->expired_at; }
    public function setExpiredAt($val) { $this->expired_at = $val; return $this; }

    public function getVerifiedAt() { return $this->verified_at; }
    public function setVerifiedAt($val) { $this->verified_at = $val; return $this; }

    public function hydrate(array $data = []): static {
        if (array_key_exists('otpID', $data)) {
            $this->setOtpID($data['otpID']);
        }
        if (array_key_exists('otpCode', $data)) {
            $this->setOtpCode($data['otpCode']);
        }
        if (array_key_exists('created_at', $data)) {
            $this->setCreatedAt($data['created_at']);
        }
        if (array_key_exists('expired_at', $data)) {
            $this->setExpiredAt($data['expired_at']);
        }
        if (array_key_exists('verified_at', $data)) {
            $this->setVerifiedAt($data['verified_at']);
        }
        return $this;
    }

    public function generate($userID) {
        // Hydrate requested properties
        $this->otpCode = sprintf("%06d", mt_rand(1, 999999));
        $this->expired_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $this->created_at = date('Y-m-d H:i:s');
        
        $this->otpID = $this->dao->generate($userID, $this->otpCode, $this->created_at, $this->expired_at);
        return $this->otpCode;
    }

    public function verify($userID, $otp) {
        $this->otpCode = $otp; 
        $now = date('Y-m-d H:i:s');
        
        $row = $this->dao->getActiveOtp($userID, $this->otpCode, $now);

        if ($row) {
            // Hydrate the model
            $this->otpID = $row['otpID'];
            $this->created_at = $row['created_at'];
            $this->expired_at = $row['expired_at'];
            $this->verified_at = $now;
            
            $this->dao->setVerified($this->otpID, $this->verified_at);
            return true;
        }
        return false;
    }

    public function isExpired($otpID) {
        $this->otpID = $otpID; // Map parameter to property
        
        $expired_at = $this->dao->getExpiredAt($this->otpID);
        
        if ($expired_at) {
            $this->expired_at = $expired_at;
            return strtotime($this->expired_at) < time();
        }
        return true;
    }
}
