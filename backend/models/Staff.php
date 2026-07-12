<?php

namespace Models;

use PDO;

class Staff extends User {
    
    public function postLostItem() {
       
    }

    public function updateLostItem() {
     
    }

    public function deleteLostItem() {
   
    }

    public function viewLostItem() {
      
    }

    public function postMarketItem() {
     
    }

    public function hydrate(array $data) {
        parent::hydrate($data);
        return $this;
    }

    public function register() {
        $this->conn->beginTransaction();
        try {
            if (!parent::register()) {
                throw new \Exception("Failed to register user");
            }
            $query = "INSERT INTO staff (userID) VALUES (:uid)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':uid', $this->userID);
            $stmt->execute();
            $this->conn->commit();
            return $this->userID;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
