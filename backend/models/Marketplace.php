<?php
namespace Models;
use Config\Database;
use PDO;

class Marketplace {
    private $conn;

    private $productID;
    private $sellerID;
    private $userID;
    private $itemName;
    private $location;
    private $phoneNumber;
    private $description;
    private $conditionType;
    private $itemImage;
    private $itemImage2;
    private $itemImage3;
    private $itemImage4;
    private $usageDuration;
    private $price = 0.00;
    private $status = 'available';

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function hydrate(array $data = []): static {
        if (array_key_exists('productID', $data)) {
            $this->setProductID($data['productID']);
        } elseif (array_key_exists('sellerID', $data)) {
            $this->setProductID($data['sellerID']);
        }
        if (array_key_exists('userID', $data)) {
            $this->setUserID($data['userID']);
        }
        if (array_key_exists('productName', $data)) {
            $this->setItemName($data['productName']);
        } elseif (array_key_exists('itemName', $data)) {
            $this->setItemName($data['itemName']);
        }
        if (array_key_exists('location', $data)) {
            $this->setLocation($data['location']);
        }
        if (array_key_exists('phone_number', $data)) {
            $this->setPhoneNumber($data['phone_number']);
        } elseif (array_key_exists('phoneNumber', $data)) {
            $this->setPhoneNumber($data['phoneNumber']);
        } elseif (array_key_exists('contactNumber', $data)) {
            $this->setPhoneNumber($data['contactNumber']);
        }
        if (array_key_exists('description', $data)) {
            $this->setDescription($data['description']);
        }

        if (array_key_exists('condition_type', $data)) {
            $this->setConditionType((bool)$data['condition_type']);
        } elseif (array_key_exists('conditionType', $data)) {
            $this->setConditionType((bool)$data['conditionType']);
        }

        if (array_key_exists('image_url', $data)) {
            $this->setItemImage($data['image_url']);
        } elseif (array_key_exists('itemImage', $data)) {
            $this->setItemImage($data['itemImage']);
        }

        if (array_key_exists('image_url2', $data)) {
            $this->setItemImage2($data['image_url2']);
        }
        if (array_key_exists('image_url3', $data)) {
            $this->setItemImage3($data['image_url3']);
        }
        if (array_key_exists('image_url4', $data)) {
            $this->setItemImage4($data['image_url4']);
        }
        if (array_key_exists('usage_duration', $data)) {
            $this->setUsageDuration($data['usage_duration']);
        }
        if (array_key_exists('price', $data)) {
            $this->setPrice($data['price']);
        }
        if (array_key_exists('status', $data)) {
            $this->setStatus($data['status']);
        }
        return $this;
    }

    public function hydrateFromRequest(array $data = []): static {
        if (array_key_exists('productName', $data)) {
            $this->setItemName($data['productName']);
        } elseif (array_key_exists('itemName', $data)) {
            $this->setItemName($data['itemName']);
        }
        if (array_key_exists('location', $data)) {
            $this->setLocation($data['location']);
        }
        if (array_key_exists('phone_number', $data)) {
            $this->setPhoneNumber($data['phone_number']);
        } elseif (array_key_exists('phoneNumber', $data)) {
            $this->setPhoneNumber($data['phoneNumber']);
        } elseif (array_key_exists('contactNumber', $data)) {
            $this->setPhoneNumber($data['contactNumber']);
        }
        if (array_key_exists('description', $data)) {
            $this->setDescription($data['description']);
        }

        if (array_key_exists('condition_type', $data)) {
            $this->setConditionType((bool)$data['condition_type']);
        } elseif (array_key_exists('conditionType', $data)) {
            $this->setConditionType((bool)$data['conditionType']);
        }

        if (array_key_exists('image_url', $data)) {
            $this->setItemImage($data['image_url']);
        } elseif (array_key_exists('itemImage', $data)) {
            $this->setItemImage($data['itemImage']);
        }

        if (array_key_exists('image_url2', $data)) {
            $this->setItemImage2($data['image_url2']);
        }
        if (array_key_exists('image_url3', $data)) {
            $this->setItemImage3($data['image_url3']);
        }
        if (array_key_exists('image_url4', $data)) {
            $this->setItemImage4($data['image_url4']);
        }
        if (array_key_exists('usage_duration', $data)) {
            $this->setUsageDuration($data['usage_duration']);
        }
        if (array_key_exists('price', $data)) {
            $this->setPrice($data['price']);
        }
        if (array_key_exists('status', $data)) {
            $this->setStatus($data['status']);
        }
        return $this;
    }

    public function getProductID() { return $this->productID; }
    public function setProductID($val) { $this->productID = $val; $this->sellerID = $val; return $this; }

    public function getSellerID() { return $this->sellerID; }
    public function setSellerID($val) { $this->sellerID = $val; $this->productID = $val; return $this; }

    public function getUserID() { return $this->userID; }
    public function setUserID($val) { $this->userID = $val; return $this; }

    public function getItemName() { return $this->itemName; }
    public function setItemName($val) { $this->itemName = $val; return $this; }

    public function getLocation() { return $this->location; }
    public function setLocation($val) { $this->location = $val; return $this; }

    public function getPhoneNumber() { return $this->phoneNumber; }
    public function setPhoneNumber($val) { $this->phoneNumber = $val; return $this; }

    public function getDescription() { return $this->description; }
    public function setDescription($val) { $this->description = $val; return $this; }

    public function getConditionType() { return $this->conditionType; }
    public function setConditionType($val) { $this->conditionType = (bool)$val; return $this; }

    public function getItemImage() { return $this->itemImage; }
    public function setItemImage($val) { $this->itemImage = $val; return $this; }

    public function getItemImage2() { return $this->itemImage2; }
    public function setItemImage2($val) { $this->itemImage2 = $val; return $this; }

    public function getItemImage3() { return $this->itemImage3; }
    public function setItemImage3($val) { $this->itemImage3 = $val; return $this; }

    public function getItemImage4() { return $this->itemImage4; }
    public function setItemImage4($val) { $this->itemImage4 = $val; return $this; }

    public function getUsageDuration() { return $this->usageDuration; }
    public function setUsageDuration($val) { $this->usageDuration = $val; return $this; }

    public function getPrice() { return $this->price; }
    public function setPrice($val) { $this->price = $val; return $this; }

    public function getStatus() { return $this->status; }
    public function setStatus($val) { $this->status = $val; return $this; }

    public function create() {
        $query = "INSERT INTO marketplace (userID, productName, price, condition_type, location, image_url, image_url2, image_url3, image_url4, usage_duration, description, phone_number) 
                  VALUES (:uid, :pname, :price, :cond, :loc, :img1, :img2, :img3, :img4, :usage, :desc, :phone)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':uid' => $this->userID,
            ':pname' => $this->itemName,
            ':price' => $this->price,
            ':cond' => $this->conditionType ? 1 : 0,
            ':loc' => $this->location,
            ':img1' => $this->itemImage,
            ':img2' => $this->itemImage2 ?? null,
            ':img3' => $this->itemImage3 ?? null,
            ':img4' => $this->itemImage4 ?? null,
            ':usage' => $this->usageDuration ?? null,
            ':desc' => $this->description,
            ':phone' => $this->phoneNumber
        ]);
        $this->productID = $this->conn->lastInsertId();
        $this->sellerID = $this->productID;
        return $this->productID;
    }

    public function update() {
        $query = "UPDATE marketplace SET productName = :pname, price = :price, status = :status WHERE productID = :pid AND userID = :uid";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':pname' => $this->itemName,
            ':price' => $this->price,
            ':status' => $this->status,
            ':pid' => $this->productID ?? $this->sellerID,
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
