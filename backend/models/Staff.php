<?php
require_once __DIR__ . '/BaseModel.php';

class Staff extends BaseModel {

   

   
    protected function getTableName() {
        return "Staff";
    }

   
    protected function getPrimaryKey() {
        return "staffID";
    }

    public function __construct() {
        parent::__construct();
    }

    
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (staffID, userID) VALUES (:staffID, :userID)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':staffID', $data['staffID']);
        $stmt->bindParam(':userID', $data['userID']);

        return $stmt->execute();
    }

    
    public function updateAdminProfile($realId) {
        return true;
    }
}
?>