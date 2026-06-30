<?php
require_once __DIR__ . '/../config/Database.php';

// Abstraction: This class cannot be instantiated directly.
abstract class BaseModel {
    protected $conn;
    protected $table;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->table = $this->getTableName();
    }

    // Abstraction: Force child classes to define their table name
    abstract protected function getTableName();

    // Inheritance: Common method available to all models
    public function findByIdBase($id, $idColumn = 'id') {
        $query = "SELECT * FROM " . $this->table . " WHERE " . $idColumn . " = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
