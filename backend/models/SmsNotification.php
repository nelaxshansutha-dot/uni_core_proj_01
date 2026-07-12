<?php
namespace Models;
use Config\Database;
use PDO;

class SmsNotification {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function send($data) {
        $stmt = $this->conn->prepare("SELECT lost_item_sms_notification FROM users WHERE userID = :uid");
        $stmt->execute([':uid' => $data['userID']]);
        $pref = $stmt->fetchColumn();

        if ($pref) {
            $query = "INSERT INTO sms_notification (lostID, userID, message) VALUES (:lid, :uid, :msg)";
            $ins = $this->conn->prepare($query);
            return $ins->execute([
                ':lid' => $data['lostID'],
                ':uid' => $data['userID'],
                ':msg' => $data['message']
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
