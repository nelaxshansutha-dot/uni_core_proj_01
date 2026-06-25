<?php
require_once __DIR__ . '/../config/Database.php';

class Staff {
    private $conn;
    private $table = "Staff";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (staffID, userID, dept) VALUES (:staffID, :userID, :dept)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':staffID', $data['staffID']);
        $stmt->bindParam(':userID', $data['userID']);
        $stmt->bindParam(':dept', $data['dept']);

        return $stmt->execute();
    }
}
?>
