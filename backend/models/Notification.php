<?php
require_once __DIR__ . '/../config/Database.php';

class Notification {
    private $conn;
    private $table_notifications = "notifications";
    private $table_recipients = "notification_recipients";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createGlobal($title, $message) {
        $this->conn->beginTransaction();
        try {
            $query = "INSERT INTO " . $this->table_notifications . " (title, message) VALUES (:title, :message)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':message', $message);
            $stmt->execute();
            
            $notification_id = $this->conn->lastInsertId();

            // Insert for all users (in a real app, this should be a background job)
            $queryUsers = "SELECT id FROM users";
            $stmtUsers = $this->conn->prepare($queryUsers);
            $stmtUsers->execute();
            $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

            $queryRecip = "INSERT INTO " . $this->table_recipients . " (notification_id, user_id) VALUES (:notif_id, :user_id)";
            $stmtRecip = $this->conn->prepare($queryRecip);

            foreach($users as $u) {
                $stmtRecip->bindValue(':notif_id', $notification_id);
                $stmtRecip->bindValue(':user_id', $u['id']);
                $stmtRecip->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getUserNotifications($user_id) {
        $query = "SELECT n.*, r.is_read, r.id as recipient_id FROM " . $this->table_notifications . " n 
                  JOIN " . $this->table_recipients . " r ON n.id = r.notification_id 
                  WHERE r.user_id = :user_id ORDER BY n.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function markAsRead($recipient_id, $user_id) {
        $query = "UPDATE " . $this->table_recipients . " SET is_read = 1 WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $recipient_id);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }
}
?>
