<?php
require_once __DIR__ . '/../config/Database.php';

class LostItem {
    private $conn;
    private $table = "lost_items";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

  public function create($data) {
    $query = "INSERT INTO lost_items
    (
        user_id,
        item_name,
        description,
        last_seen_datetime,
        last_seen_place,
        contact_number,
        item_image
    )
    VALUES
    (
        :user_id,
        :item_name,
        :description,
        :last_seen_datetime,
        :last_seen_place,
        :contact_number,
        :item_image
    )";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(':user_id', $data['user_id']);
    $stmt->bindParam(':item_name', $data['item_name']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':last_seen_datetime', $data['last_seen_datetime']);
    $stmt->bindParam(':last_seen_place', $data['last_seen_place']);
    $stmt->bindParam(':contact_number', $data['contact_number']);
    $stmt->bindParam(':item_image', $data['item_image']);

    return $stmt->execute();
}

    public function getAll() {
    $query = "SELECT l.*, u.enrollment_no
              FROM lost_items l
              JOIN users u ON l.user_id = u.id
              ORDER BY l.created_at DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    public function getByUser($user_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   
    
    public function delete($lost_id, $user_id) {
        $query = "DELETE FROM " . $this->table . " WHERE lost_id = :lost_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':lost_id', $lost_id);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }
}
?>
