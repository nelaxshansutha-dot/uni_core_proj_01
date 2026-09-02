<?php
namespace DAO;

use PDO;

class StaffDAO extends BaseDAO {

    public function insertStaff($staffID, $userID) {
        $query = "INSERT INTO staff (staffID, userID) VALUES (:sid, :uid)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':sid', $staffID);
        $stmt->bindValue(':uid', $userID, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function beginTransaction() {
        $this->db->beginTransaction();
    }

    public function commit() {
        $this->db->commit();
    }

    public function rollBack() {
        $this->db->rollBack();
    }
    
    public function inTransaction() {
        return $this->db->inTransaction();
    }

    public function getStaffIDByUserId($userID) {
        $staffStmt = $this->db->prepare("SELECT staffID FROM staff WHERE userID = :uid LIMIT 1");
        $staffStmt->execute([':uid' => $userID]);
        $staffRow = $staffStmt->fetch(PDO::FETCH_ASSOC);
        return $staffRow ? $staffRow['staffID'] : null;
    }
}
