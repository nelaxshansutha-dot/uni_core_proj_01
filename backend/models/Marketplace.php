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
            (userID, productName, description, price, condition_type, location, phone_number, usage_duration, image_url, image_url2, image_url3, image_url4, status) 
            VALUES (:userID, :productName, :description, :price, :condition_type, :location, :phone_number, :usage_duration, :image_url, :image_url2, :image_url3, :image_url4, :status)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':userID',         $data['userID']);
        $stmt->bindParam(':productName',      $data['productName']);
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
    public function findById($productID) {
        $query = "SELECT m.*, CONCAT(u.fname, ' ', u.lname) AS seller_name 
                  FROM " . $this->table . " m 
                  JOIN Users u ON m.userID = u.userID 
                  WHERE m.productID = :productID LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':productID', $productID);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $query = "SELECT m.*, s.enrollmentNo as enrollment_no, 
                          CONCAT(u.fname, ' ', u.lname) AS seller_name
                  FROM " . $this->table . " m 
                  JOIN Users u ON m.userID = u.userID
                  LEFT JOIN Student s ON u.userID = s.userID
                  ORDER BY m.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($productID, $userID, $status) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE productID = :productID AND userID = :userID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status',    $status);
        $stmt->bindParam(':productID', $productID);
        $stmt->bindParam(':userID',    $userID);
        return $stmt->execute();
    }

    public function update($data, $userID) {
        $query = "UPDATE " . $this->table . " 
            SET productName = :productName, 
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
            WHERE productID = :productID AND userID = :userID";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':productName',      $data['productName']);
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
        $stmt->bindParam(':productID',      $data['productID']);
        $stmt->bindParam(':userID',         $userID);

        return $stmt->execute();
    }

    public function delete($productID, $userID = null) {
        if ($userID !== null) {
            $query = "DELETE FROM " . $this->table . " WHERE productID = :productID AND userID = :userID";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':productID', $productID);
            $stmt->bindParam(':userID',    $userID);
        } else {
            $query = "DELETE FROM " . $this->table . " WHERE productID = :productID";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':productID', $productID);
        }
        return $stmt->execute();
    }
}
?>
