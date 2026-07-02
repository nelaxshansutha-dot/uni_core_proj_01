<?php
require_once __DIR__ . '/BaseModel.php';

// Inheritance: LostItem inherits core database tools from BaseModel
class LostItem extends BaseModel {

    // Encapsulation: Define the table name internally
    protected function getTableName() {
        return "Lost_items";
    }

    public function __construct() {
        parent::__construct();
    }

    // Abstraction: Implement abstract create method from BaseModel
    public function create($data) {
        $query = "INSERT INTO Lost_items
        (
            userID,
            item_name,
            description,
            last_seen_datetime,
            last_seen_place,
            contact_number,
            item_image
        )
        VALUES
        (
            :userID,
            :item_name,
            :description,
            :last_seen_datetime,
            :last_seen_place,
            :contact_number,
            :item_image
        )";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':userID', $data['user_id']);
        $stmt->bindParam(':item_name', $data['item_name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':last_seen_datetime', $data['last_seen_datetime']);
        $stmt->bindParam(':last_seen_place', $data['last_seen_place']);
        $stmt->bindParam(':contact_number', $data['contact_number']);
        $stmt->bindParam(':item_image', $data['item_image']);

        return $stmt->execute();
    }

    // Polymorphism: Override default findById to query using lostID
    public function findById($id) {
        return $this->findByIdBase($id, 'lostID');
    }

    public function getAll() {
        $query = "SELECT l.*, l.lostID as lost_id, l.userID as user_id, s.enrollmentNo as enrollment_no
                  FROM Lost_items l
                  JOIN Users u ON l.userID = u.userID
                  LEFT JOIN Student s ON u.userID = s.userID
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
                  item_name = :item_name, 
                  description = :description, 
                  last_seen_datetime = :last_seen_datetime, 
                  last_seen_place = :last_seen_place, 
                  contact_number = :contact_number";
                  
        if ($data['item_image'] !== null) {
            $query .= ", item_image = :item_image";
        }
        
        $query .= " WHERE lostID = :lost_id AND userID = :user_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':item_name', $data['item_name']);
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
}
?>
