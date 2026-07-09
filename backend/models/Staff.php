<?php
require_once __DIR__ . '/BaseModel.php';

// Inheritance: Staff inherits database operations from BaseModel
class Staff extends BaseModel {

    // Encapsulation: Define the table name internally
    protected function getTableName() {
        return "Staff";
    }

    // Encapsulation: Define the primary key internally
    protected function getPrimaryKey() {
        return "staffID";
    }

    public function __construct() {
        parent::__construct();
    }

    // Abstraction: Implementing the abstract create method from BaseModel
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