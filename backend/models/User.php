<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $conn;
    private $table = "users";

    public $id;
    public $enrollment_no;
    public $email;
    public $phone_number;
    public $password_hash;
    public $role;
    public $is_verified;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function findByEnrollment($enrollment_no) {
        $query = "SELECT * FROM " . $this->table . " WHERE enrollment_no = :enrollment_no LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':enrollment_no', $enrollment_no);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (enrollment_no, email, phone_number, password_hash, role, is_verified) VALUES (:enrollment_no, :email, :phone_number, :password_hash, :role, FALSE)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':enrollment_no', $data['enrollment_no']);
        $stmt->bindParam(':email', $data['email']);
        $phone = isset($data['phone_number']) ? $data['phone_number'] : null;
        $stmt->bindParam(':phone_number', $phone);
        $stmt->bindParam(':password_hash', $data['password_hash']);
        $stmt->bindParam(':role', $data['role']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function markAsVerified($id) {
        $query = "UPDATE " . $this->table . " SET is_verified = TRUE WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    public function updatePassword($id, $new_hash) {
        $query = "UPDATE " . $this->table . " SET password_hash = :hash WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hash', $new_hash);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
