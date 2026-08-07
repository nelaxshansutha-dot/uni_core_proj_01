<?php
namespace Models;
use Config\Database;
use PDO;

class AppNotification {
    private $conn;

    private $appID;
    private $repID;
    private $enrollmentNo;
    private $message;
    private $createdAt;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function hydrate(array $data = []): static {
        if (array_key_exists('appID', $data)) {
            $this->setAppID($data['appID']);
        }
        if (array_key_exists('repID', $data)) {
            $this->setRepID($data['repID']);
        }
        if (array_key_exists('enrollmentNo', $data)) {
            $this->setEnrollmentNo($data['enrollmentNo']);
        }
        if (array_key_exists('message', $data)) {
            $this->setMessage($data['message']);
        }
        if (array_key_exists('created_at', $data)) {
            $this->setCreatedAt($data['created_at']);
        }
        return $this;
    }

    public function hydrateFromRequest(array $data = []): static {
        if (array_key_exists('message', $data)) {
            $this->setMessage($data['message']);
        }
        return $this;
    }

    public function getAppID() { return $this->appID; }
    public function setAppID($val) { $this->appID = $val; return $this; }

    public function getRepID() { return $this->repID; }
    public function setRepID($val) { $this->repID = $val; return $this; }

    public function getEnrollmentNo() { return $this->enrollmentNo; }
    public function setEnrollmentNo($val) { $this->enrollmentNo = $val; return $this; }

    public function getMessage() { return $this->message; }
    public function setMessage($val) { $this->message = $val; return $this; }

    public function getCreatedAt() { return $this->createdAt; }
    public function setCreatedAt($val) { $this->createdAt = $val; return $this; }

    public function send() {
        
        $stmt = $this->conn->prepare("SELECT peer_learning_app_notification FROM users u JOIN student s ON u.userID = s.userID WHERE s.enrollmentNo = :enr");
        $stmt->execute([':enr' => $this->enrollmentNo]);
        $pref = $stmt->fetchColumn();

        if ($pref) {
            $query = "INSERT INTO app_notification (repID, enrollmentNo, message) VALUES (:repid, :enr, :msg)";
            $ins = $this->conn->prepare($query);
            return $ins->execute([
                ':repid' => $this->repID,
                ':enr' => $this->enrollmentNo,
                ':msg' => $this->message
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
      
        return true; 
    }
}
