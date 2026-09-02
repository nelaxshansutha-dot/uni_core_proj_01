<?php
namespace Models;

use DAO\MarketplaceDAO;
use PDO;

class Marketplace {
    private $dao;

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
    private $price;
    private $status;

    public function __construct() {
        $this->dao = new MarketplaceDAO();
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
            $this->setConditionType($data['condition_type']);
        } elseif (array_key_exists('conditionType', $data)) {
            $this->setConditionType($data['conditionType']);
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
            $this->setConditionType($data['condition_type']);
        } elseif (array_key_exists('conditionType', $data)) {
            $this->setConditionType($data['conditionType']);
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
    public function setConditionType($val) { $this->conditionType = $val; return $this; }

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
        $this->productID = $this->dao->create(
            $this->userID,
            $this->itemName,
            $this->price,
            $this->conditionType,
            $this->location,
            $this->itemImage,
            $this->itemImage2 ?? null,
            $this->itemImage3 ?? null,
            $this->itemImage4 ?? null,
            $this->usageDuration ?? null,
            $this->description,
            $this->phoneNumber
        );
        $this->sellerID = $this->productID;
        return $this->productID;
    }

    public function update() {
        return $this->dao->update(
            $this->productID,
            $this->sellerID,
            $this->userID,
            $this->itemName,
            $this->price,
            $this->conditionType,
            $this->location,
            $this->phoneNumber,
            $this->description,
            $this->usageDuration,
            $this->itemImage,
            $this->itemImage2,
            $this->itemImage3,
            $this->itemImage4,
            $this->status
        );
    }

    public function delete($productID, $userID) {
        return $this->dao->delete($productID, $userID);
    }

    public function view($productID = null) {
        return $this->dao->view($productID);
    }

    public function flag($productID) {
        return $this->dao->flag($productID);
    }
}
