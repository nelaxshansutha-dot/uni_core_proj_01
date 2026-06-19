<?php
require_once __DIR__ . '/../config/Database.php';

class Notes {
    private $conn;
    private $table = "notes";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (uploader_id, title, description, file_url, course_code, year, semester) VALUES (:uploader_id, :title, :description, :file_url, :course_code, :year, :semester)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':uploader_id', $data['uploader_id']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':file_url', $data['file_url']);
        $stmt->bindParam(':course_code', $data['course_code']);
        $stmt->bindParam(':year', $data['year']);
        $stmt->bindParam(':semester', $data['semester']);

        return $stmt->execute();
    }

    public function getAll($filters = []) {
        $query = "SELECT n.*, s.enrollmentNo as enrollment_no, c.course_name FROM " . $this->table . " n 
                  JOIN users u ON n.uploader_id = u.id 
                  LEFT JOIN Student s ON u.id = s.userID
                  JOIN courses c ON n.course_code = c.course_code 
                  WHERE 1=1";
        
        $params = [];
        if (!empty($filters['course_code'])) {
            $query .= " AND n.course_code = :course_code";
            $params[':course_code'] = $filters['course_code'];
        }
        if (!empty($filters['year'])) {
            $query .= " AND n.year = :year";
            $params[':year'] = $filters['year'];
        }
        if (!empty($filters['semester'])) {
            $query .= " AND n.semester = :semester";
            $params[':semester'] = $filters['semester'];
        }

        $query .= " ORDER BY n.created_at DESC";
        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function delete($id, $uploader_id) {
        // Also ensure admin can delete, but for now simple check
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND uploader_id = :uploader_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':uploader_id', $uploader_id);
        return $stmt->execute();
    }
}
?>
