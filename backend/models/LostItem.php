<?php
require_once __DIR__ . '/../config/Database.php';

class LostItem {
    private $conn;
    private $table = "Lost_items";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

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

   
    
    public function delete($lost_id, $user_id) {
        $query = "DELETE FROM " . $this->table . " WHERE lostID = :lost_id AND userID = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':lost_id', $lost_id);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }
}
?>
