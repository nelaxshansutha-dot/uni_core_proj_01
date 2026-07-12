<?php
namespace Models;
use Config\Database;
use PDO;

class Marketplace {
    private $conn;


    private $sellerID;
    private $userID;
    private $itemName;
    private $location;
    private $phoneNumber;
    private $description;
    private $conditionType;
    private $itemImage;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }


    public function getSellerID() { return $this->sellerID; }
    public function setSellerID($val) { $this->sellerID = $val; }

    public function getUserID() { return $this->userID; }
    public function setUserID($val) { $this->userID = $val; }

    public function getItemName() { return $this->itemName; }
    public function setItemName($val) { $this->itemName = $val; }

    public function getLocation() { return $this->location; }
    public function setLocation($val) { $this->location = $val; }

    public function getPhoneNumber() { return $this->phoneNumber; }
    public function setPhoneNumber($val) { $this->phoneNumber = $val; }

    public function getDescription() { return $this->description; }
    public function setDescription($val) { $this->description = $val; }

    public function getConditionType() { return $this->conditionType; }
    public function setConditionType($val) { $this->conditionType = $val; }

    public function getItemImage() { return $this->itemImage; }
    public function setItemImage($val) { $this->itemImage = $val; }

    public function hydrate($data) {
        $this->sellerID = $data['sellerID'] ?? $this->sellerID;
        $this->userID = $data['userID'] ?? $this->userID;
        
        $this->itemName = $data['productName'] ?? $data['itemName'] ?? $this->itemName;
        $this->location = $data['location'] ?? $this->location;
        $this->phoneNumber = $data['phone_number'] ?? $data['phoneNumber'] ?? $this->phoneNumber;
        $this->description = $data['description'] ?? $this->description;
        
        // Handle boolean condition type mapping
        if (isset($data['condition_type'])) {
            $this->conditionType = (bool)$data['condition_type'];
        } elseif (isset($data['conditionType'])) {
            $this->conditionType = (bool)$data['conditionType'];
        }
        
      
        $this->itemImage = $data['image_url'] ?? $data['itemImage'] ?? $this->itemImage;

        return $this;
    }

    public function create($data) {
        $this->hydrate($data);
        
        // Extract required database fields that are NOT part of the strict class properties
        $price = $data['price'] ?? 0.00;
        $img2 = $data['image_url2'] ?? null;
        $img3 = $data['image_url3'] ?? null;
        $img4 = $data['image_url4'] ?? null;
        $usage = $data['usage_duration'] ?? null;

        $query = "INSERT INTO marketplace (userID, productName, price, condition_type, location, image_url, image_url2, image_url3, image_url4, usage_duration, description, phone_number) 
                  VALUES (:uid, :pname, :price, :cond, :loc, :img1, :img2, :img3, :img4, :usage, :desc, :phone)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':uid' => $this->userID,
            ':pname' => $this->itemName,
            ':price' => $price,
            ':cond' => $this->conditionType ? 1 : 0,
            ':loc' => $this->location,
            ':img1' => $this->itemImage,
            ':img2' => $img2,
            ':img3' => $img3,
            ':img4' => $img4,
            ':usage' => $usage,
            ':desc' => $this->description,
            ':phone' => $this->phoneNumber
        ]);
        return $this->conn->lastInsertId();
    }

    public function update($productID, $userID, $data) {
        $this->hydrate($data);
        if ($userID) $this->userID = $userID;
        
        // Extract required database fields that are NOT part of the strict class properties
        $price = $data['price'] ?? 0.00;
        $status = $data['status'] ?? 'available';

        $query = "UPDATE marketplace SET productName = :pname, price = :price, status = :status WHERE productID = :pid AND userID = :uid";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':pname' => $this->itemName,
            ':price' => $price,
            ':status' => $status,
            ':pid' => $productID,
            ':uid' => $this->userID
        ]);
    }

    public function delete($productID, $userID) {
        $stmt = $this->conn->prepare("DELETE FROM marketplace WHERE productID = :pid AND userID = :uid");
        return $stmt->execute([':pid' => $productID, ':uid' => $userID]);
    }

    public function view($productID = null) {
        if ($productID) {
            $stmt = $this->conn->prepare("SELECT * FROM marketplace WHERE productID = :pid");
            $stmt->execute([':pid' => $productID]);
            return $stmt->fetch();
        } else {
            $stmt = $this->conn->query("SELECT * FROM marketplace ORDER BY created_at DESC");
            return $stmt->fetchAll();
        }
    }

    public function flag($productID) {
        $stmt = $this->conn->prepare("UPDATE marketplace SET is_flagged = 1 WHERE productID = :pid");
        return $stmt->execute([':pid' => $productID]);
    }
}
