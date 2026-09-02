<?php
namespace DAO;

use PDO;

class SmsNotificationDAO extends BaseDAO {
    public function getUserPreference($userID) {
        $stmt = $this->db->prepare("SELECT lost_item_sms_notification FROM users WHERE userID = :uid");
        $stmt->execute([':uid' => $userID]);
        return $stmt->fetchColumn();
    }

    public function insert($lostID, $userID, $message) {
        $query = "INSERT INTO sms_notification (lostID, userID, message) VALUES (:lid, :uid, :msg)";
        $ins = $this->db->prepare($query);
        return $ins->execute([
            ':lid' => $lostID,
            ':uid' => $userID,
            ':msg' => $message
        ]);
    }

    public function view($userID) {
        $stmt = $this->db->prepare("SELECT * FROM sms_notification WHERE userID = :uid ORDER BY created_at DESC");
        $stmt->execute([':uid' => $userID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
