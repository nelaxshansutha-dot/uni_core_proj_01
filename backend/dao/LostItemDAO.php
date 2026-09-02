<?php
namespace DAO;

use Exception;
use PDO;

class LostItemDAO extends BaseDAO {
    public function create($userID, $itemName, $lsdt, $lastSeenPlace, $description, $itemImage, $contactNumber) {
        try {
            $query = "INSERT INTO lost_items (userID, lostItemName, last_seen_datetime, last_seen_place, description, item_image, contact_number) 
                      VALUES (:uid, :name, :lsdt, :lsp, :desc, :img, :phone)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':uid' => $userID,
                ':name' => $itemName,
                ':lsdt' => $lsdt,
                ':lsp' => $lastSeenPlace,
                ':desc' => $description,
                ':img' => $itemImage,
                ':phone' => $contactNumber
            ]);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error creating lost item: " . $e->getMessage());
        }
    }

    public function update($lostID, $userID, $itemName, $lsdt, $lastSeenPlace, $description, $contactNumber, $status) {
        try {
            $query = "UPDATE lost_items SET 
                      lostItemName = :name, 
                      last_seen_datetime = :lsdt,
                      last_seen_place = :lsp,
                      description = :desc,
                      contact_number = :phone,
                      status = :status 
                      WHERE lostID = :id AND userID = :uid";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':name' => $itemName,
                ':lsdt' => $lsdt,
                ':lsp' => $lastSeenPlace,
                ':desc' => $description,
                ':phone' => $contactNumber,
                ':status' => $status,
                ':id' => $lostID,
                ':uid' => $userID
            ]);
        } catch (Exception $e) {
            throw new Exception("Error updating lost item: " . $e->getMessage());
        }
    }

    public function delete($lostID, $userID) {
        try {
            $stmt = $this->db->prepare("DELETE FROM lost_items WHERE lostID = :id AND userID = :uid");
            return $stmt->execute([':id' => $lostID, ':uid' => $userID]);
        } catch (Exception $e) {
            throw new Exception("Error deleting lost item: " . $e->getMessage());
        }
    }

    public function view($lostID = null) {
        try {
            if ($lostID) {
                $stmt = $this->db->prepare("SELECT * FROM lost_items WHERE lostID = :id");
                $stmt->execute([':id' => $lostID]);
                return $stmt->fetch();
            } else {
                $stmt = $this->db->query("SELECT * FROM lost_items ORDER BY created_at DESC");
                return $stmt->fetchAll();
            }
        } catch (Exception $e) {
            throw new Exception("Error viewing lost item(s): " . $e->getMessage());
        }
    }

    public function deleteByAdmin($lostID) {
        $stmt = $this->db->prepare("DELETE FROM lost_items WHERE lostID = ?");
        return $stmt->execute([$lostID]);
    }

    public function getLostItemWithOwner($lostID) {
        $stmt = $this->db->prepare("SELECT u.email, l.lostItemName as title FROM lost_items l JOIN users u ON l.userID = u.userID WHERE l.lostID = ?");
        $stmt->execute([$lostID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRecentByUser($userID, $limit) {
        $stmt = $this->db->prepare("SELECT lostItemName, created_at FROM lost_items WHERE userID = :uid ORDER BY created_at DESC LIMIT :lim");
        $stmt->bindValue(':uid', $userID, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
