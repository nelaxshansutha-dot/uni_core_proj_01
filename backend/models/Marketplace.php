<?php
require_once __DIR__ . '/../config/Database.php';

class Marketplace {
    private $conn;
    private $table = "marketplace";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (seller_id, item_name, description, price, image_url, status) VALUES (:seller_id, :item_name, :description, :price, :image_url, :status)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':seller_id', $data['seller_id']);
        $stmt->bindParam(':item_name', $data['item_name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':image_url', $data['image_url']);
        $stmt->bindParam(':status', $data['status']);

        return $stmt->execute();
    }

    public function getAll() {
        $query = "SELECT m.*, u.enrollment_no FROM " . $this->table . " m JOIN users u ON m.seller_id = u.id ORDER BY m.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $seller_id, $status) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id AND seller_id = :seller_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':seller_id', $seller_id);
        return $stmt->execute();
    }
    
    public function delete($id, $seller_id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND seller_id = :seller_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':seller_id', $seller_id);
        return $stmt->execute();
    }
}
?>
