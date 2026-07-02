<?php
require_once __DIR__ . '/BaseModel.php';

// Inheritance: Marketplace inherits base database functionality from BaseModel
class Marketplace extends BaseModel {

    // Encapsulation: Define the table name internally
    protected function getTableName() {
        return "marketplace";
    }

    public function __construct() {
        parent::__construct();
    }

    // Abstraction: Implement abstract create method from BaseModel
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

    // Polymorphism: Override default findById to retrieve item with seller info
    public function findById($id) {
        $query = "SELECT m.*, CONCAT(u.fname, ' ', u.lname) AS seller_name 
                  FROM " . $this->table . " m 
                  JOIN Users u ON m.seller_id = u.userID 
                  WHERE m.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    public function delete($id, $seller_id = null) {
        if ($seller_id !== null) {
            $query = "DELETE FROM " . $this->table . " WHERE id = :id AND seller_id = :seller_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id',        $id);
            $stmt->bindParam(':seller_id', $seller_id);
        } else {
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id',        $id);
        }
        return $stmt->execute();
    }
}
?>
