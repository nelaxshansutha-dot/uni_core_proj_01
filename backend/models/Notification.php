<?php
require_once __DIR__ . '/BaseModel.php';

// Inheritance: Notification inherits core database configuration from BaseModel
class Notification extends BaseModel {
    // Encapsulation: Keep table references encapsulated inside the class
    private $table_notifications = "notifications";
    private $table_recipients = "notification_recipients";

    // Encapsulation: Define primary table name internally
    protected function getTableName() {
        return "notifications";
    }

    // Encapsulation: Define the primary key internally
    protected function getPrimaryKey() {
        return "id";
    }

    public function __construct() {
        parent::__construct();
    }

    // Abstraction: Implement abstract create method from BaseModel
    public function create($data) {
        if (isset($data['userId'])) {
            return $this->createForUser($data['userId'], $data['title'], $data['message']);
        } elseif (isset($data['userIds'])) {
            return $this->createForUsers($data['userIds'], $data['title'], $data['message']);
        } else {
            return $this->createGlobal($data['title'], $data['message']);
        }
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

    public function createForUsers($userIds, $title, $message) {
        if (empty($userIds)) return true;
        $this->conn->beginTransaction();
        try {
            $query = "INSERT INTO " . $this->table_notifications . " (title, message) VALUES (:title, :message)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':message', $message);
            $stmt->execute();
            
            $notification_id = $this->conn->lastInsertId();

            $queryRecip = "INSERT INTO " . $this->table_recipients . " (notification_id, user_id) VALUES (:notif_id, :user_id)";
            $stmtRecip = $this->conn->prepare($queryRecip);

            foreach($userIds as $userId) {
                $stmtRecip->bindValue(':notif_id', $notification_id);
                $stmtRecip->bindValue(':user_id', $userId);
                $stmtRecip->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function createForUser($userId, $title, $message) {
        return $this->createForUsers([$userId], $title, $message);
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
