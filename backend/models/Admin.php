<?php

namespace Models;

use PDO;

class Admin extends User {
    
    public function viewSystemLogs() {
      
    }

    public function register() {
        $this->conn->beginTransaction();
        try {
            if (!parent::register()) {
                throw new \Exception("Failed to register user");
            }
            $query = "INSERT INTO admin (userID) VALUES (:uid)";
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

    public function manageUsers() {
       
    }

    public function assignCourseRep() {
      
    }

    public function deactivateUser($targetUserId) {
        $query = "UPDATE users SET is_active = 0 WHERE userID = :uid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $targetUserId);
        return $stmt->execute();
    }

    public function monitorPlatform() {
        
    }

    public function hydrate(array $data) {
        parent::hydrate($data);
        return $this;
    }
}
