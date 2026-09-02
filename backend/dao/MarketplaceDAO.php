<?php
namespace DAO;

use PDO;

class MarketplaceDAO extends BaseDAO {
    public function create($userID, $itemName, $price, $conditionType, $location, $itemImage, $itemImage2, $itemImage3, $itemImage4, $usageDuration, $description, $phoneNumber) {
        $query = "INSERT INTO marketplace (userID, productName, price, condition_type, location, image_url, image_url2, image_url3, image_url4, usage_duration, description, phone_number) 
                  VALUES (:uid, :pname, :price, :cond, :loc, :img1, :img2, :img3, :img4, :usage, :desc, :phone)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':uid' => $userID,
            ':pname' => $itemName,
            ':price' => $price,
            ':cond' => $conditionType,
            ':loc' => $location,
            ':img1' => $itemImage,
            ':img2' => $itemImage2 ?? null,
            ':img3' => $itemImage3 ?? null,
            ':img4' => $itemImage4 ?? null,
            ':usage' => $usageDuration ?? null,
            ':desc' => $description,
            ':phone' => $phoneNumber
        ]);
        return $this->db->lastInsertId();
    }

    public function update($productID, $sellerID, $userID, $itemName, $price, $conditionType, $location, $phoneNumber, $description, $usageDuration, $itemImage, $itemImage2, $itemImage3, $itemImage4, $status) {
        $sets  = [];
        $params = [
            ':pid' => $productID ?? $sellerID,
            ':uid' => $userID
        ];

        if ($itemName      !== null) { $sets[] = 'productName = :pname';       $params[':pname']  = $itemName; }
        if ($price         !== null) { $sets[] = 'price = :price';              $params[':price']  = $price; }
        if ($conditionType !== null) { $sets[] = 'condition_type = :cond';      $params[':cond']   = $conditionType; }
        if ($location      !== null) { $sets[] = 'location = :loc';             $params[':loc']    = $location; }
        if ($phoneNumber   !== null) { $sets[] = 'phone_number = :phone';       $params[':phone']  = $phoneNumber; }
        if ($description   !== null) { $sets[] = 'description = :desc';         $params[':desc']   = $description; }
        if ($usageDuration !== null) { $sets[] = 'usage_duration = :usage';     $params[':usage']  = $usageDuration; }
        if ($itemImage     !== null) { $sets[] = 'image_url = :img1';           $params[':img1']   = $itemImage; }
        if ($itemImage2    !== null) { $sets[] = 'image_url2 = :img2';          $params[':img2']   = $itemImage2; }
        if ($itemImage3    !== null) { $sets[] = 'image_url3 = :img3';          $params[':img3']   = $itemImage3; }
        if ($itemImage4    !== null) { $sets[] = 'image_url4 = :img4';          $params[':img4']   = $itemImage4; }
        if ($status        !== null) { $sets[] = 'status = :status';            $params[':status'] = $status; }

        if (empty($sets)) return false;

        $query = 'UPDATE marketplace SET ' . implode(', ', $sets) . ' WHERE productID = :pid AND userID = :uid';
        $stmt  = $this->db->prepare($query);
        return $stmt->execute($params);
    }

    public function delete($productID, $userID) {
        $stmt = $this->db->prepare("DELETE FROM marketplace WHERE productID = :pid AND userID = :uid");
        return $stmt->execute([':pid' => $productID, ':uid' => $userID]);
    }

    public function view($productID = null) {
        if ($productID) {
            $stmt = $this->db->prepare(
                "SELECT m.*, CONCAT(u.fname, ' ', u.lname) AS seller_name
                 FROM marketplace m
                 LEFT JOIN users u ON m.userID = u.userID
                 WHERE m.productID = :pid"
            );
            $stmt->execute([':pid' => $productID]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->db->query(
                "SELECT m.*, CONCAT(u.fname, ' ', u.lname) AS seller_name
                 FROM marketplace m
                 LEFT JOIN users u ON m.userID = u.userID
                 ORDER BY m.created_at DESC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function flag($productID) {
        $stmt = $this->db->prepare("UPDATE marketplace SET is_flagged = 1 WHERE productID = :pid");
        return $stmt->execute([':pid' => $productID]);
    }

    public function deleteByAdmin($productID) {
        $stmt = $this->db->prepare("DELETE FROM marketplace WHERE productID = ?");
        return $stmt->execute([$productID]);
    }

    public function getMarketItemWithOwner($productID) {
        $stmt = $this->db->prepare("SELECT u.email, m.productName as title FROM marketplace m JOIN users u ON m.userID = u.userID WHERE m.productID = ?");
        $stmt->execute([$productID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRecentByUser($userID, $limit) {
        $stmt = $this->db->prepare("SELECT productName, price, created_at FROM marketplace WHERE userID = :uid ORDER BY created_at DESC LIMIT :lim");
        $stmt->bindValue(':uid', $userID, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
