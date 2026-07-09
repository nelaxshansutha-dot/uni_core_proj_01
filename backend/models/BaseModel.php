<?php
require_once __DIR__ . '/../config/Database.php';

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

    // 
    abstract public function create($data);

    // Inheritance: Common read method inherited by all models
    public function findByIdBase($id, $idColumn = 'id') {
        $query = "SELECT * FROM " . $this->table . " WHERE " . $idColumn . " = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Polymorphism: Default findById method that subclasses can override
    public function findById($id) {
        return $this->findByIdBase($id, 'id');
    }
}
?>
