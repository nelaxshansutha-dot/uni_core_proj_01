<?php
require_once __DIR__ . '/BaseModel.php';

// Inheritance: LostItem inherits core database tools from BaseModel
class LostItem extends BaseModel {


    protected function getTableName() {
        return "Lost_items";
    }

    
    protected function getPrimaryKey() {
        return "lostID";
    }

    public function __construct() {
        parent::__construct();
    }


    public function create($data) {
        $query = "INSERT INTO Lost_items
        (userID,lostItemName, description, last_seen_datetime,last_seen_place,contact_number,item_image
        )
        VALUES
        ( :userID,:lostItemName,:description,:last_seen_datetime,:last_seen_place,:contact_number,:item_image )";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':userID', $data['user_id']);
        $stmt->bindParam(':lostItemName', $data['lostItemName']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':last_seen_datetime', $data['last_seen_datetime']);
        $stmt->bindParam(':last_seen_place', $data['last_seen_place']);
        $stmt->bindParam(':contact_number', $data['contact_number']);
        $stmt->bindParam(':item_image', $data['item_image']);

        return $stmt->execute();
    }

    

    public function getAll() {
        $query = "SELECT l.*, l.lostID as lost_id, l.userID as user_id, s.enrollmentNo as enrollment_no
                  FROM Lost_items l
                  JOIN Users u ON l.userID = u.userID
                  LEFT JOIN Student s ON u.userID = s.userID
                  WHERE l.status NOT IN ('removed', 'hidden')
                  ORDER BY l.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        
    public function getByUser($user_id) {
        $query = "SELECT *, lostID as lost_id, userID as user_id FROM " . $this->table . " WHERE userID = :userID ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userID', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($data) {
        $query = "UPDATE " . $this->table . " SET 
                  lostItemName = :lostItemName, 
                  description = :description, 
                  last_seen_datetime = :last_seen_datetime, 
                  last_seen_place = :last_seen_place, 
                  contact_number = :contact_number";
                  
        if ($data['item_image'] !== null) {
            $query .= ", item_image = :item_image";
        }
        
        $query .= " WHERE lostID = :lost_id AND userID = :user_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':lostItemName', $data['lostItemName']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':last_seen_datetime', $data['last_seen_datetime']);
        $stmt->bindParam(':last_seen_place', $data['last_seen_place']);
        $stmt->bindParam(':contact_number', $data['contact_number']);
        $stmt->bindParam(':lost_id', $data['lost_id']);
        $stmt->bindParam(':user_id', $data['user_id']);

        if ($data['item_image'] !== null) {
            $stmt->bindParam(':item_image', $data['item_image']);
        }

        return $stmt->execute();
    }

    public function delete($lost_id, $user_id = null) {
        if ($user_id !== null) {
            $query = "DELETE FROM " . $this->table . " WHERE lostID = :lost_id AND userID = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':lost_id', $lost_id);
            $stmt->bindParam(':user_id', $user_id);
        } else {
            $query = "DELETE FROM " . $this->table . " WHERE lostID = :lost_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':lost_id', $lost_id);
        }
        return $stmt->execute();
    }

    public function countAll() {
        return $this->conn->query("SELECT COUNT(*) FROM " . $this->table)->fetchColumn();
    }

    public function getAdminContent() {
        $query = "SELECT l.lostID as lost_id, l.lostItemName, l.last_seen_datetime, l.item_image, l.contact_number as contact_no, l.created_at, l.status, u.email, s.enrollmentNo as enrollment_no
                  FROM " . $this->table . " l 
                  JOIN Users u ON l.userID = u.userID 
                  LEFT JOIN Student s ON u.userID = s.userID
                  ORDER BY l.lostID DESC";
        $stmt = $this->conn->query($query);
        $lostItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $lostItems;
    }

    public function updateAdminStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET status = ? WHERE lostID = ?");
        return $stmt->execute([$status, $id]);
    }

    public function getLatestItems($limit = 5) {
        $query = "SELECT lostID as id, lostItemName as title, 'lost_item' as type, created_at FROM " . $this->table . " WHERE status NOT IN ('removed', 'hidden') ORDER BY created_at DESC LIMIT " . intval($limit);
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>