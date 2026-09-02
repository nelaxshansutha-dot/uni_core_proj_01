<?php
namespace DAO;

use PDO;

class UserDAO extends BaseDAO {
    
    public function insertUser($fname, $lname, $email, $phoneNum, $hash, $role) {
        $query = "INSERT INTO users (fname, lname, email, phoneNum, hash_password, role) 
                  VALUES (:fname, :lname, :email, :phoneNum, :hash, :role)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':fname', $fname);
        $stmt->bindParam(':lname', $lname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phoneNum', $phoneNum);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':role', $role);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        throw new \Exception("Database insert into users failed: " . implode(" ", $stmt->errorInfo()));
    }

    public function updateLastLogin($userID) {
        $upd = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE userID = :uid");
        $upd->bindParam(':uid', $userID);
        return $upd->execute();
    }

    public function updateProfile($userID, $fname, $lname, $phoneNum, $smsPref, $peerPref, $hash_password = null) {
        $query = "UPDATE users SET fname = :fname, lname = :lname, phoneNum = :phoneNum, lost_item_sms_notification = :smsPref, peer_learning_app_notification = :peerPref";
        if ($hash_password) {
            $query .= ", hash_password = :hash";
        }
        $query .= " WHERE userID = :uid";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':fname', $fname);
        $stmt->bindParam(':lname', $lname);
        $stmt->bindParam(':phoneNum', $phoneNum);
        $stmt->bindParam(':smsPref', $smsPref);
        $stmt->bindParam(':peerPref', $peerPref);
        if ($hash_password) {
            $stmt->bindParam(':hash', $hash_password);
        }
        $stmt->bindParam(':uid', $userID);
        return $stmt->execute();
    }

    public function changePassword($userID, $newHash) {
        $query = "UPDATE users SET hash_password = :hash WHERE userID = :uid";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':hash', $newHash);
        $stmt->bindParam(':uid', $userID);
        return $stmt->execute();
    }

    public function getValidOTP($userID, $otpCode, $now) {
        $query = "SELECT * FROM otp_verification WHERE userID = :uid AND otp_code = :otp AND expired_at > :now AND verified_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':uid', $userID);
        $stmt->bindParam(':otp', $otpCode);
        $stmt->bindParam(':now', $now);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markOtpVerified($otpID, $now) {
        $upd = $this->db->prepare("UPDATE otp_verification SET verified_at = :now WHERE otpID = :id");
        $upd->bindParam(':now', $now);
        $upd->bindParam(':id', $otpID);
        return $upd->execute();
    }

    public function markUserVerified($userID) {
        $updUser = $this->db->prepare("UPDATE users SET is_verified = 1 WHERE userID = :uid");
        $updUser->bindParam(':uid', $userID);
        return $updUser->execute();
    }

    public function loadByIdentifier($identifier, $role) {
        if ($role === 'student') {
            $sql = "SELECT u.*, s.enrollmentNo, s.courseID, s.std_year 
                    FROM users u 
                    JOIN student s ON u.userID = s.userID 
                    WHERE s.enrollmentNo = :identifier";
        } elseif ($role === 'course_representative') {
            $sql = "SELECT u.*, s.enrollmentNo, s.courseID, s.std_year, c.repID, c.rep_id_string, c.is_first_login, c.hash_password as rep_hash_password
                    FROM course_representative c
                    JOIN users u ON u.userID = c.userID
                    LEFT JOIN student s ON s.userID = c.userID
                    WHERE c.rep_id_string = :identifier1 OR s.enrollmentNo = :identifier2";
        } elseif ($role === 'staff') {
            $sql = "SELECT u.*, st.staffID FROM users u JOIN staff st ON u.userID = st.userID WHERE st.staffID = :identifier1 OR u.email = :identifier2";
        } elseif ($role === 'admin') {
            $sql = "SELECT u.*, a.adminID FROM users u JOIN admin a ON u.userID = a.userID WHERE a.adminID = :identifier1 OR u.email = :identifier2";
        } else {
            return null;
        }

        $stmt = $this->db->prepare($sql);
        if (in_array($role, ['course_representative', 'staff', 'admin'])) {
            $stmt->bindParam(':identifier1', $identifier);
            $stmt->bindParam(':identifier2', $identifier);
        } else {
            $stmt->bindParam(':identifier', $identifier);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function loadByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($userID) {
        $sql = "SELECT * FROM users WHERE userID = :uid";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':uid', $userID);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserIdByEmail($email) {
        $sql = "SELECT userID FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function updateRole($userID, $role) {
        $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE userID = :uid");
        return $stmt->execute([':role' => $role, ':uid' => $userID]);
    }

    public function getActiveUserIdsExcept($excludeUserID) {
        $stmt = $this->db->prepare("SELECT userID FROM users WHERE is_active = 1 AND userID != :uid");
        $stmt->execute([':uid' => $excludeUserID]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getOptedInPhoneNumbers() {
        $stmt = $this->db->query("SELECT phoneNum FROM users WHERE phoneNum IS NOT NULL AND phoneNum != '' AND lost_item_sms_notification = 1");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function ensureLostItemPreferencesColumnsExist() {
        try {
            $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS lost_item_sms_notification TINYINT(1) DEFAULT 0");
            $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS has_seen_lost_item_popup TINYINT(1) DEFAULT 0");
        } catch (\Exception $e) {}
    }

    public function updateLostItemPreferences($userID, $smsPref, $popupPref) {
        $stmt = $this->db->prepare("UPDATE users SET lost_item_sms_notification = :sms, has_seen_lost_item_popup = :popup WHERE userID = :uid");
        return $stmt->execute([
            ':sms' => $smsPref,
            ':popup' => $popupPref,
            ':uid' => $userID
        ]);
    }

    public function updatePassword($userID, $hash) {
        $stmt = $this->db->prepare("UPDATE users SET hash_password = :hash WHERE userID = :uid");
        return $stmt->execute([':hash' => $hash, ':uid' => $userID]);
    }

    public function ensureProfileColumnsExist() {
        try {
            $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS lost_item_sms_notification TINYINT(1) DEFAULT 0");
            $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS peer_learning_app_notification TINYINT(1) DEFAULT 1");
            $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS has_seen_lost_item_popup TINYINT(1) DEFAULT 0");
        } catch (\Exception $e) {}
    }
    public function updateBasicProfile($userID, $fname, $lname, $phoneNum, $email) {
        $stmt = $this->db->prepare("UPDATE users SET fname = :fname, lname = :lname, phoneNum = :phoneNum, email = :email WHERE userID = :uid");
        return $stmt->execute([
            ':fname' => $fname,
            ':lname' => $lname,
            ':phoneNum' => $phoneNum,
            ':email' => $email,
            ':uid' => $userID
        ]);
    }

    public function updateActiveStatus($userID, $isActive) {
        $stmt = $this->db->prepare("UPDATE users SET is_active = :isActive WHERE userID = :uid");
        return $stmt->execute([
            ':isActive' => $isActive,
            ':uid' => $userID
        ]);
    }
}
