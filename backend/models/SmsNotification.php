<?php
namespace Models;
use Config\Database;
use PDO;

class SmsNotification {
    private $smsID;
    private $lostID;
    private $userID;
    private $message;
    private $created_at;
    private $dao;

    public function __construct() {
        $this->dao = new \DAO\SmsNotificationDAO();
    }

    public function hydrate(array $data = []): static {
        if (array_key_exists('smsID', $data)) {
            $this->setSmsID($data['smsID']);
        }
        if (array_key_exists('lostID', $data)) {
            $this->setLostID($data['lostID']);
        }
        if (array_key_exists('userID', $data)) {
            $this->setUserID($data['userID']);
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

        $pref = $this->dao->getUserPreference($this->userID);

        if ($pref) {
            return $this->dao->insert($this->lostID, $this->userID, $this->message);
        }
        return false;
    }

    public function view($userID) {
        return $this->dao->view($userID);
    }
}
