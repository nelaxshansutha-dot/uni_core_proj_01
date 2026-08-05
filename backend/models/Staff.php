<?php

namespace Models;

use PDO;
use Exception;

class Staff extends User {
    
    protected $staffID;

    /**
     * Delegates creation to the LostItem model and attaches the staff's userID.
     */
    public function postLostItem(array $data) {
        try {
            $lostItem = new LostItem($data);
            $lostItem->setUserID($this->getUserID()); // Prevent spoofing by using the logged-in user's ID
            return $lostItem->create();
        } catch (Exception $e) {
            throw new Exception("Failed to post lost item: " . $e->getMessage());
        }
    }

    public function updateLostItem($lostID, array $data): bool {
        try {
            $lostItem = new LostItem($data);
            $lostItem->setUserID($this->getUserID()); // Ownership check
            return $lostItem->update($lostID);
        } catch (Exception $e) {
            throw new Exception("Failed to update lost item: " . $e->getMessage());
        }
    }

    /**
     * Delegates delete to the LostItem model and attaches the staff's userID for ownership check.
     */
    public function deleteLostItem($lostID): bool {
        try {
            $lostItem = new LostItem();
            return $lostItem->delete($lostID, $this->getUserID()); // Passes userID to ensure they own it
        } catch (Exception $e) {
            throw new Exception("Failed to delete lost item: " . $e->getMessage());
        }
    }

    /**
     * Delegates view to the LostItem model.
     */
    public function viewLostItem($lostID = null) {
        try {
            $lostItem = new LostItem();
            return $lostItem->view($lostID);
        } catch (Exception $e) {
            throw new Exception("Failed to view lost item: " . $e->getMessage());
        }
    }

    /**
     * Delegates marketplace item creation to Marketplace model and attaches staff's userID.
     */
    public function postMarketItem(array $data) {
        try {
            $marketItem = new Marketplace($data);
            $marketItem->setUserID($this->getUserID()); // Prevent spoofing
            return $marketItem->create($data); // Marketplace requires $data passed to create()
        } catch (Exception $e) {
            throw new Exception("Failed to post market item: " . $e->getMessage());
        }
    }

    public function __construct(array $data = []) {
        parent::__construct($data);
        if (isset($data['enrollmentNo'])) {
            $this->staffID = $data['enrollmentNo'];
        } elseif (isset($data['staffID'])) {
            $this->staffID = $data['staffID'];
        }
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
            $stmt->bindParam(':uid', $this->getUserID());
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
