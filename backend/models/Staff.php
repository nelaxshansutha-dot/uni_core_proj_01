<?php

namespace Models;

use PDO;
use Exception;

class Staff extends User {
    
    protected $staffID;

   
    public function postLostItem(LostItem $lostItem) {
        try {
            $lostItem->setUserID($this->getUserID()); // Prevent spoofing by using the logged-in user's ID
            return $lostItem->create();
        } catch (Exception $e) {
            throw new Exception("Failed to post lost item: " . $e->getMessage());
        }
    }

    public function updateLostItem(LostItem $lostItem): bool {
        try {
            $lostItem->setUserID($this->getUserID()); // Ownership check
            return $lostItem->update($lostItem->getLostID());
        } catch (Exception $e) {
            throw new Exception("Failed to update lost item: " . $e->getMessage());
        }
    }

    
    public function deleteLostItem($lostID): bool {
        try {
            $lostItem = new LostItem();
            return $lostItem->delete($lostID, $this->getUserID()); // Passes userID to ensure they own it
        } catch (Exception $e) {
            throw new Exception("Failed to delete lost item: " . $e->getMessage());
        }
    }

   
    public function viewLostItem($lostID = null) {
        try {
            $lostItem = new LostItem();
            return $lostItem->view($lostID);
        } catch (Exception $e) {
            throw new Exception("Failed to view lost item: " . $e->getMessage());
        }
    }

    
    public function postMarketItem(Marketplace $marketItem) {
        try {
            $marketItem->setUserID($this->getUserID()); // Prevent spoofing
            return $marketItem->create(); // parameterless create
        } catch (Exception $e) {
            throw new Exception("Failed to post market item: " . $e->getMessage());
        }
    }

    public function __construct() {
        parent::__construct();
    }

    public function hydrate(array $data = []): static {
        parent::hydrate($data);
        if (array_key_exists('enrollmentNo', $data)) {
            $this->setStaffID($data['enrollmentNo']);
        } elseif (array_key_exists('staffID', $data)) {
            $this->setStaffID($data['staffID']);
        } elseif (array_key_exists('staff_id', $data)) {
            $this->setStaffID($data['staff_id']);
        }
        return $this;
    }

    public function hydrateFromRequest(array $data = []): static {
        parent::hydrateFromRequest($data);
        if (array_key_exists('enrollmentNo', $data)) {
            $this->setStaffID($data['enrollmentNo']);
        } elseif (array_key_exists('staffID', $data)) {
            $this->setStaffID($data['staffID']);
        } elseif (array_key_exists('staff_id', $data)) {
            $this->setStaffID($data['staff_id']);
        }
        return $this;
    }

    public function getStaffID() {
        return $this->staffID;
    }

    public function setStaffID($staffID) {
        $this->staffID = $staffID;
        return $this;
    }

    public function register() {
        $ownsTransaction = false;
        if (!$this->conn->inTransaction()) {
            $this->conn->beginTransaction();
            $ownsTransaction = true;
        }
        try {
            if (!parent::register()) {
                throw new Exception("Failed to register user");
            }
            $query = "INSERT INTO staff (staffID, userID) VALUES (:sid, :uid)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':sid', $this->staffID);
            $stmt->bindValue(':uid', $this->getUserID(), PDO::PARAM_INT);
            $stmt->execute();
            if ($ownsTransaction) {
                $this->conn->commit();
            }
            return $this->getUserID();
        } catch (Exception $e) {
            if ($ownsTransaction) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}
