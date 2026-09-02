<?php

namespace Models;

use Config\Database;
use PDO;
use Firebase\JWT\JWT;

abstract class User {
    protected $conn;
    protected $userDAO;

   
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
        $this->conn = Database::getInstance()->getConnection(); // Kept temporarily for backwards compatibility in subclasses
        $this->userDAO = new \DAO\UserDAO();
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
        $this->userID = $this->userDAO->insertUser(
            $this->fname, $this->lname, $this->email, $this->phoneNum, $this->hash_password, $this->role
        );
        return $this->userID;
    }

    public function login($password) {
        if ($this->hash_password && password_verify($password, $this->hash_password)) {
            if (!$this->is_active) {
                throw new \Exception("Account is deactivated.");
            }
            if (!$this->is_verified) {
                throw new \Exception("Account is not verified.");
            }
            
            $this->userDAO->updateLastLogin($this->userID);
            return true;
        }
        return false;
    }

    public function updateProfile() {
        return $this->userDAO->updateProfile(
            $this->userID,
            $this->fname,
            $this->lname,
            $this->phoneNum,
            $this->lost_item_sms_notification,
            $this->peer_learning_app_notification,
            $this->hash_password
        );
    }

    public function changePassword($newHash) {
        return $this->userDAO->changePassword($this->userID, $newHash);
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
        $otpRow = $this->userDAO->getValidOTP($this->userID, $otpCode, $now);

        if ($otpRow) {
            $this->userDAO->markOtpVerified($otpRow['otpID'], $now);
            $this->userDAO->markUserVerified($this->userID);
            return true;
        }
        return false;
    }

    public static function loadByIdentifier(string $identifier, string $role) {
        $dao = new \DAO\UserDAO();
        $fullData = $dao->loadByIdentifier($identifier, $role);

        if ($fullData) {
            $fullData['role'] = $role;
            return self::createInstanceFromRole($role, $fullData);
        }
        return null;
    }

    public static function loadByEmail(string $email) {
        $dao = new \DAO\UserDAO();
        $userData = $dao->loadByEmail($email);

        if ($userData) {
            $role = $userData['role'];
            return self::createInstanceFromRole($role, $userData);
        }
        return null;
    }
}
