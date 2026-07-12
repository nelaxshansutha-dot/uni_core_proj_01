<?php
namespace Models;
use Config\Database;
use PDO;

class LostItem {
    private $conn;
    
    private $lostID;
    private $userID;
    private $itemName;
    private $LastSeenDate;
    private $lastSeenTime;
    private $itemLmage;
    private $contactNumber;
    private $description;
    private $lastSeenPlace;
    private $status;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Getters and Setters
    public function getLostID() { return $this->lostID; }
    public function setLostID($val) { $this->lostID = $val; }

    public function getUserID() { return $this->userID; }
    public function setUserID($val) { $this->userID = $val; }

    public function getItemName() { return $this->itemName; }
    public function setItemName($val) { $this->itemName = $val; }

    public function getLastSeenDate() { return $this->LastSeenDate; }
    public function setLastSeenDate($val) { $this->LastSeenDate = $val; }

    public function getLastSeenTime() { return $this->lastSeenTime; }
    public function setLastSeenTime($val) { $this->lastSeenTime = $val; }

    public function getItemLmage() { return $this->itemLmage; }
    public function setItemLmage($val) { $this->itemLmage = $val; }

    public function getContactNumber() { return $this->contactNumber; }
    public function setContactNumber($val) { $this->contactNumber = $val; }

    public function hydrate($data) {
        $this->lostID = $data['lostID'] ?? $this->lostID;
        $this->userID = $data['userID'] ?? $this->userID;
        $this->itemName = $data['lostItemName'] ?? $data['itemName'] ?? $this->itemName;
        
        // Handle datetime splitting if provided as a single string from frontend
        if (!empty($data['last_seen_datetime'])) {
            $parts = explode('T', $data['last_seen_datetime']);
            if (count($parts) == 2) {
                $this->LastSeenDate = $parts[0];
                $this->lastSeenTime = $parts[1];
            } else {
                $parts = explode(' ', $data['last_seen_datetime']);
                $this->LastSeenDate = $parts[0] ?? null;
                $this->lastSeenTime = $parts[1] ?? null;
            }
        }

        $this->itemLmage = $data['item_image'] ?? $data['itemLmage'] ?? $this->itemLmage;
        $this->contactNumber = $data['contact_number'] ?? $data['contactNumber'] ?? $this->contactNumber;
        
        $this->description = $data['description'] ?? $this->description;
        $this->lastSeenPlace = $data['last_seen_place'] ?? $this->lastSeenPlace;
        $this->status = $data['status'] ?? $this->status ?? 'lost';
        
        return $this;
    }

    public function create($data = null) {
        if ($data) $this->hydrate($data);
        
        $query = "INSERT INTO lost_items (userID, lostItemName, last_seen_datetime, last_seen_place, description, item_image, contact_number) 
                  VALUES (:uid, :name, :lsdt, :lsp, :desc, :img, :phone)";
        $stmt = $this->conn->prepare($query);
        
        // Combine date and time for DB
        $lsdt = null;
        if ($this->LastSeenDate && $this->lastSeenTime) {
            $lsdt = $this->LastSeenDate . ' ' . $this->lastSeenTime;
        }

        $stmt->execute([
            ':uid' => $this->userID,
            ':name' => $this->itemName,
            ':lsdt' => $lsdt,
            ':lsp' => $this->lastSeenPlace,
            ':desc' => $this->description,
            ':img' => $this->itemLmage,
            ':phone' => $this->contactNumber
        ]);
        $this->lostID = $this->conn->lastInsertId();
        return $this->lostID;
    }

    public function update($lostID, $data = null) {
        if ($data) $this->hydrate($data);
        
        $query = "UPDATE lost_items SET lostItemName = :name, status = :status WHERE lostID = :id AND userID = :uid";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':name' => $this->itemName,
            ':status' => $this->status,
            ':id' => $lostID,
            ':uid' => $this->userID
        ]);
    }

    public function delete($lostID, $userID) {
        $stmt = $this->conn->prepare("DELETE FROM lost_items WHERE lostID = :id AND userID = :uid");
        return $stmt->execute([':id' => $lostID, ':uid' => $userID]);
    }

    public function view($lostID = null) {
        if ($lostID) {
            $stmt = $this->conn->prepare("SELECT * FROM lost_items WHERE lostID = :id");
            $stmt->execute([':id' => $lostID]);
            return $stmt->fetch();
        } else {
            $stmt = $this->conn->query("SELECT * FROM lost_items ORDER BY created_at DESC");
            return $stmt->fetchAll();
        }
    }
}
