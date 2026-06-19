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
        $query = "INSERT INTO " . $this->table . " 
            (seller_id, item_name, description, price, condition_type, location, phone_number, usage_duration, image_url, image_url2, image_url3, image_url4, status) 
            VALUES (:seller_id, :item_name, :description, :price, :condition_type, :location, :phone_number, :usage_duration, :image_url, :image_url2, :image_url3, :image_url4, :status)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':seller_id',      $data['seller_id']);
        $stmt->bindParam(':item_name',      $data['item_name']);
        $stmt->bindParam(':description',    $data['description']);
        $stmt->bindParam(':price',          $data['price']);
        $stmt->bindParam(':condition_type', $data['condition_type']);
        $stmt->bindParam(':location',       $data['location']);
        $stmt->bindParam(':phone_number',   $data['phone_number']);
        $stmt->bindParam(':usage_duration', $data['usage_duration']);
        $stmt->bindParam(':image_url',      $data['image_url']);
        $stmt->bindParam(':image_url2',     $data['image_url2']);
        $stmt->bindParam(':image_url3',     $data['image_url3']);
        $stmt->bindParam(':image_url4',     $data['image_url4']);
        $stmt->bindParam(':status',         $data['status']);

        return $stmt->execute();
    }

    public function getAll() {
        $query = "SELECT m.*, s.enrollmentNo as enrollment_no, 
                         CONCAT(u.fname, ' ', u.lname) AS seller_name
                  FROM " . $this->table . " m 
                  JOIN Users u ON m.seller_id = u.userID
                  LEFT JOIN Student s ON u.userID = s.userID
                  ORDER BY m.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $seller_id, $status) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id AND seller_id = :seller_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status',    $status);
        $stmt->bindParam(':id',        $id);
        $stmt->bindParam(':seller_id', $seller_id);
        return $stmt->execute();
    }

    public function update($data, $seller_id) {
        $query = "UPDATE " . $this->table . " 
            SET item_name = :item_name, 
                description = :description, 
                price = :price, 
                condition_type = :condition_type, 
                location = :location, 
                phone_number = :phone_number, 
                usage_duration = :usage_duration, 
                image_url = :image_url, 
                image_url2 = :image_url2, 
                image_url3 = :image_url3, 
                image_url4 = :image_url4
            WHERE id = :id AND seller_id = :seller_id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':item_name',      $data['item_name']);
        $stmt->bindParam(':description',    $data['description']);
        $stmt->bindParam(':price',          $data['price']);
        $stmt->bindParam(':condition_type', $data['condition_type']);
        $stmt->bindParam(':location',       $data['location']);
        $stmt->bindParam(':phone_number',   $data['phone_number']);
        $stmt->bindParam(':usage_duration', $data['usage_duration']);
        $stmt->bindParam(':image_url',      $data['image_url']);
        $stmt->bindParam(':image_url2',     $data['image_url2']);
        $stmt->bindParam(':image_url3',     $data['image_url3']);
        $stmt->bindParam(':image_url4',     $data['image_url4']);
        $stmt->bindParam(':id',             $data['id']);
        $stmt->bindParam(':seller_id',      $seller_id);

        return $stmt->execute();
    }

    public function delete($id, $seller_id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND seller_id = :seller_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id',        $id);
        $stmt->bindParam(':seller_id', $seller_id);
        return $stmt->execute();
    }
}
?>
