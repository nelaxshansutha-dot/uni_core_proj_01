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

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
        $this->status = 'lost';
    }

    public function hydrate(array $data = []): static
    {
        if (array_key_exists('lostID', $data)) {
            $this->setLostID($data['lostID']);
        }
        if (array_key_exists('userID', $data)) {
            $this->setUserID($data['userID']);
        }
        if (array_key_exists('lostItemName', $data)) {
            $this->setItemName($data['lostItemName']);
        } elseif (array_key_exists('itemName', $data)) {
            $this->setItemName($data['itemName']);
        }

        if (array_key_exists('last_seen_datetime', $data) && !empty($data['last_seen_datetime'])) {
            $parts = preg_split('/[T ]/', $data['last_seen_datetime'], 2);
            $this->setLastSeenDate($parts[0] ?? null);
            $this->setLastSeenTime($parts[1] ?? null);
        }

        if (array_key_exists('item_image', $data)) {
            $this->setItemImage($data['item_image']);
        } elseif (array_key_exists('itemImage', $data)) {
            $this->setItemImage($data['itemImage']);
        } elseif (array_key_exists('itemLmage', $data)) {
            $this->setItemImage($data['itemLmage']);
        }

        if (array_key_exists('contact_number', $data)) {
            $this->setContactNumber($data['contact_number']);
        } elseif (array_key_exists('contactNumber', $data)) {
            $this->setContactNumber($data['contactNumber']);
        }

        if (array_key_exists('description', $data)) {
            $this->setDescription($data['description']);
        }
        if (array_key_exists('last_seen_place', $data)) {
            $this->setLastSeenPlace($data['last_seen_place']);
        }
        if (array_key_exists('status', $data)) {
            $this->setStatus($data['status']);
        }
        return $this;
    }

    public function hydrateFromRequest(array $data = []): static
    {
        if (array_key_exists('lostItemName', $data)) {
            $this->setItemName($data['lostItemName']);
        } elseif (array_key_exists('itemName', $data)) {
            $this->setItemName($data['itemName']);
        }

        if (array_key_exists('last_seen_datetime', $data) && !empty($data['last_seen_datetime'])) {
            $parts = preg_split('/[T ]/', $data['last_seen_datetime'], 2);
            $this->setLastSeenDate($parts[0] ?? null);
            $this->setLastSeenTime($parts[1] ?? null);
        }

        if (array_key_exists('item_image', $data)) {
            $this->setItemImage($data['item_image']);
        } elseif (array_key_exists('itemImage', $data)) {
            $this->setItemImage($data['itemImage']);
        } elseif (array_key_exists('itemLmage', $data)) {
            $this->setItemImage($data['itemLmage']);
        }

        if (array_key_exists('contact_number', $data)) {
            $this->setContactNumber($data['contact_number']);
        } elseif (array_key_exists('contactNumber', $data)) {
            $this->setContactNumber($data['contactNumber']);
        }

        if (array_key_exists('description', $data)) {
            $this->setDescription($data['description']);
        }
        if (array_key_exists('last_seen_place', $data)) {
            $this->setLastSeenPlace($data['last_seen_place']);
        }
        if (array_key_exists('status', $data)) {
            $this->setStatus($data['status']);
        }
        return $this;
    }

    public function getLostID() { return $this->lostID; }
    public function setLostID($val) { $this->lostID = $val; return $this; }

    public function getUserID() { return $this->userID; }
    public function setUserID($val) { $this->userID = $val; return $this; }

    public function getItemName() { return $this->itemName; }
    public function setItemName($val) { $this->itemName = $val; return $this; }

    public function getLastSeenDate() { return $this->LastSeenDate; }
    public function setLastSeenDate($val) { $this->LastSeenDate = $val; return $this; }

    public function getLastSeenTime() { return $this->lastSeenTime; }
    public function setLastSeenTime($val) { $this->lastSeenTime = $val; return $this; }

   
    public function getItemImage() { return $this->itemImage; }
    public function setItemImage($val) { $this->itemImage = $val; return $this; }

    public function getContactNumber() { return $this->contactNumber; }
    public function setContactNumber($val) { $this->contactNumber = $val; return $this; }

    // Added missing getters/setters for description, lastSeenPlace, and status
    public function getDescription() { return $this->description; }
    public function setDescription($val) { $this->description = $val; return $this; }

    public function getLastSeenPlace() { return $this->lastSeenPlace; }
    public function setLastSeenPlace($val) { $this->lastSeenPlace = $val; return $this; }

    public function getStatus() { return $this->status; }
    public function setStatus($val) { $this->status = $val; return $this; }

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

    public function update()
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
                ':id' => $this->lostID,
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
