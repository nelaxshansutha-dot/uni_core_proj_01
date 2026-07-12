<?php
namespace Models;
use Config\Database;
use PDO;

class AppNotification {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function send($data) {
        // Respect user's peer_learning_app_notification preference
        $stmt = $this->conn->prepare("SELECT peer_learning_app_notification FROM users u JOIN student s ON u.userID = s.userID WHERE s.enrollmentNo = :enr");
        $stmt->execute([':enr' => $data['enrollmentNo']]);
        $pref = $stmt->fetchColumn();

        if ($pref) {
            $query = "INSERT INTO app_notification (repID, enrollmentNo, message) VALUES (:repid, :enr, :msg)";
            $ins = $this->conn->prepare($query);
            return $ins->execute([
                ':repid' => $data['repID'] ?? null,
                ':enr' => $data['enrollmentNo'],
                ':msg' => $data['message']
            ]);
        }
        return false;
    }

    public function view($enrollmentNo) {
        $stmt = $this->conn->prepare("SELECT * FROM app_notification WHERE enrollmentNo = :enr ORDER BY created_at DESC");
        $stmt->execute([':enr' => $enrollmentNo]);
        return $stmt->fetchAll();
    }

    public function markAsRead($appID) {
        // Usually involves a flag, but schema doesn't have is_read.
        // We could delete it or just return true if it's stateless.
        return true; 
    }
}
