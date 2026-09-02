<?php
namespace Models;
use Config\Database;
use PDO;

class AppNotification {
    private $appID;
    private $repID;
    private $enrollmentNo;
    private $message;
    private $createdAt;
    private $dao;

    public function __construct() {
        $this->dao = new \DAO\AppNotificationDAO();
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
        
        $pref = $this->dao->getUserPreference($this->enrollmentNo);

        if ($pref) {
            return $this->dao->insert($this->repID, $this->enrollmentNo, $this->message);
        }
        return false;
    }

    public function view($enrollmentNo) {
        return $this->dao->view($enrollmentNo);
    }

    public function markAsRead($appID) {
      
        return true; 
    }
}
