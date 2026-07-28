<?php
namespace Models;
use Config\Database;
use PDO;

class SmsNotification {
    private $conn;

    private $smsID;
    private $lostID;
    private $userID;
    private $message;
    private $created_at;

    public function __construct(array $data = []) {
        $this->conn = Database::getInstance()->getConnection();
        if (!empty($data)) {
            $this->smsID = $data['smsID'] ?? null;
            $this->lostID = $data['lostID'] ?? null;
            $this->userID = $data['userID'] ?? null;
            $this->message = $data['message'] ?? null;
            $this->created_at = $data['created_at'] ?? null;
        }
    }

    public function getSmsID() { return $this->smsID; }
    public function setSmsID($val) { $this->smsID = $val; return $this; }

    public function getLostID() { return $this->lostID; }
    public function setLostID($val) { $this->lostID = $val; return $this; }

    public function getUserID() { return $this->userID; }
    public function setUserID($val) { $this->userID = $val; return $this; }

    public function getMessage() { return $this->message; }
    public function setMessage($val) { $this->message = $val; return $this; }

    public function getCreatedAt() { return $this->created_at; }
    public function setCreatedAt($val) { $this->created_at = $val; return $this; }

    public function send() {
        if (!$this->userID || !$this->lostID || !$this->message) {
            return false;
        }

        $stmt = $this->conn->prepare("SELECT lost_item_sms_notification FROM users WHERE userID = :uid");
        $stmt->execute([':uid' => $this->userID]);
        $pref = $stmt->fetchColumn();

        if ($pref) {
            $query = "INSERT INTO sms_notification (lostID, userID, message) VALUES (:lid, :uid, :msg)";
            $ins = $this->conn->prepare($query);
            return $ins->execute([
                ':lid' => $this->lostID,
                ':uid' => $this->userID,
                ':msg' => $this->message
            ]);
        }
        return false;
    }

    public function view($userID) {
        $stmt = $this->conn->prepare("SELECT * FROM sms_notification WHERE userID = :uid ORDER BY created_at DESC");
        $stmt->execute([':uid' => $userID]);
        return $stmt->fetchAll();
    }
}
