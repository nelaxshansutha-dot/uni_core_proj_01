<?php
require_once __DIR__ . '/../config/Database.php';

class Staff {
    private $conn;
    private $table = "staff";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (user_id, first_name, last_name, department) VALUES (:user_id, :first_name, :last_name, :department)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':department', $data['department']);

        return $stmt->execute();
    }
}
?>
