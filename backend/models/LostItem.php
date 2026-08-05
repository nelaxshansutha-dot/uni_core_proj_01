<?php

namespace Models;

use Config\Database;
use PDO;
use Exception;

class LostItem
{
    private $conn;

    private $lostID;
    private $userID;
    private $itemName;
    private $LastSeenDate;
    private $lastSeenTime;
    private $itemImage; 
    private $contactNumber;
    private $description;
    private $lastSeenPlace;
    private $status;

    public function __construct(array $data = [])
    {
        $this->conn = Database::getInstance()->getConnection();
        if (!empty($data)) {
            $this->lostID = $data['lostID'] ?? $this->lostID;
            $this->userID = $data['userID'] ?? $this->userID;
            $this->itemName = $data['lostItemName'] ?? $data['itemName'] ?? $this->itemName;

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

          
            $this->itemImage = $data['item_image'] ?? $data['itemImage'] ?? $data['itemLmage'] ?? $this->itemImage;
            $this->contactNumber = $data['contact_number'] ?? $data['contactNumber'] ?? $this->contactNumber;

            $this->description = $data['description'] ?? $this->description;
            $this->lastSeenPlace = $data['last_seen_place'] ?? $this->lastSeenPlace;
            $this->status = $data['status'] ?? $this->status ?? 'lost';
        }
    }

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

   
    public function getItemImage() { return $this->itemImage; }
    public function setItemImage($val) { $this->itemImage = $val; }

    public function getContactNumber() { return $this->contactNumber; }
    public function setContactNumber($val) { $this->contactNumber = $val; }

    // Added missing getters/setters for description, lastSeenPlace, and status
    public function getDescription() { return $this->description; }
    public function setDescription($val) { $this->description = $val; }

    public function getLastSeenPlace() { return $this->lastSeenPlace; }
    public function setLastSeenPlace($val) { $this->lastSeenPlace = $val; }

    public function getStatus() { return $this->status; }
    public function setStatus($val) { $this->status = $val; }

    public function create()
    {
        try {
            $query = "INSERT INTO lost_items (userID, lostItemName, last_seen_datetime, last_seen_place, description, item_image, contact_number) 
                      VALUES (:uid, :name, :lsdt, :lsp, :desc, :img, :phone)";
            $stmt = $this->conn->prepare($query);

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
                ':img' => $this->itemImage,
                ':phone' => $this->contactNumber
            ]);
            $this->lostID = $this->conn->lastInsertId();
            return $this->lostID;
        } catch (Exception $e) {
            throw new Exception("Error creating lost item: " . $e->getMessage());
        }
    }

    public function update($lostID)
    {
        try {
            $query = "UPDATE lost_items SET 
                      lostItemName = :name, 
                      last_seen_datetime = :lsdt,
                      last_seen_place = :lsp,
                      description = :desc,
                      contact_number = :phone,
                      status = :status 
                      WHERE lostID = :id AND userID = :uid";
            
            $stmt = $this->conn->prepare($query);
            
            $lsdt = null;
            if ($this->LastSeenDate && $this->lastSeenTime) {
                $lsdt = $this->LastSeenDate . ' ' . $this->lastSeenTime;
            }

            return $stmt->execute([
                ':name' => $this->itemName,
                ':lsdt' => $lsdt,
                ':lsp' => $this->lastSeenPlace,
                ':desc' => $this->description,
                ':phone' => $this->contactNumber,
                ':status' => $this->status,
                ':id' => $lostID,
                ':uid' => $this->userID
            ]);
        } catch (Exception $e) {
            throw new Exception("Error updating lost item: " . $e->getMessage());
        }
    }

    public function delete($lostID, $userID)
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM lost_items WHERE lostID = :id AND userID = :uid");
            return $stmt->execute([':id' => $lostID, ':uid' => $userID]);
        } catch (Exception $e) {
            throw new Exception("Error deleting lost item: " . $e->getMessage());
        }
    }

    public function view($lostID = null)
    {
        try {
            if ($lostID) {
                $stmt = $this->conn->prepare("SELECT * FROM lost_items WHERE lostID = :id");
                $stmt->execute([':id' => $lostID]);
                return $stmt->fetch();
            } else {
                $stmt = $this->conn->query("SELECT * FROM lost_items ORDER BY created_at DESC");
                return $stmt->fetchAll();
            }
        } catch (Exception $e) {
            throw new Exception("Error viewing lost item(s): " . $e->getMessage());
        }
    }
}
