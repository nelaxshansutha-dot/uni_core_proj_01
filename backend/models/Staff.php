<?php

namespace Models;

use PDO;
use Exception;

class Staff extends User {
    
    /**
     * Delegates creation to the LostItem model and attaches the staff's userID.
     */
    public function postLostItem(array $data) {
        try {
            $lostItem = new LostItem($data);
            $lostItem->setUserID($this->userID); // Prevent spoofing by using the logged-in user's ID
            return $lostItem->create();
        } catch (Exception $e) {
            throw new Exception("Failed to post lost item: " . $e->getMessage());
        }
    }

    /**
     * Delegates update to the LostItem model and attaches the staff's userID for ownership check.
     */
    public function updateLostItem($lostID, array $data): bool {
        try {
            $lostItem = new LostItem($data);
            $lostItem->setUserID($this->userID); // Ownership check
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
            return $lostItem->delete($lostID, $this->userID); // Passes userID to ensure they own it
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
            $marketItem->setUserID($this->userID); // Prevent spoofing
            return $marketItem->create($data); // Marketplace requires $data passed to create()
        } catch (Exception $e) {
            throw new Exception("Failed to post market item: " . $e->getMessage());
        }
    }

    public function __construct(array $data = []) {
        parent::__construct($data);
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
            $query = "INSERT INTO staff (userID) VALUES (:uid)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':uid', $this->userID);
            $stmt->execute();
            if ($ownsTransaction) {
                $this->conn->commit();
            }
            return $this->userID;
        } catch (Exception $e) {
            if ($ownsTransaction) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}
