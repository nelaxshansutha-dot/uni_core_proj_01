<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/Database.php';

class AdminController {
    
    public function searchStudents($query) {
        $db = (new Database())->getConnection();
        $q = "%" . $query . "%";
        $sql = "SELECT u.id, u.enrollment_no, u.role, s.first_name, s.last_name, s.course, s.year 
                FROM users u 
                JOIN students s ON u.id = s.user_id 
                WHERE (u.enrollment_no LIKE :q OR s.first_name LIKE :q OR s.last_name LIKE :q) 
                AND u.role IN ('student', 'rep')";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':q', $q);
        $stmt->execute();
        
        Response::success("Students found", $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function assignRep($data) {
        $missing = Validator::required(['user_id'], $data);
        if (!empty($missing)) {
            Response::error("Missing user_id");
        }

        $db = (new Database())->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'student'");
        $stmt->bindParam(':id', $data['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Response::error("User not found or already a rep/staff.");
        }

        // Generate password
        $raw_password = 'REP' . rand(1000, 9999);
        $hashed = password_hash($raw_password, PASSWORD_BCRYPT);

        $update = $db->prepare("UPDATE users SET role = 'rep', password_hash = :hash WHERE id = :id");
        $update->bindParam(':hash', $hashed);
        $update->bindParam(':id', $data['user_id']);

        if ($update->execute()) {
            // Mock send email
            file_put_contents(__DIR__ . '/../admin_log.txt', "Assigned rep to {$user['email']}. Credentials: Username: {$user['enrollment_no']}, Password: {$raw_password}\n", FILE_APPEND);
            Response::success("Successfully assigned as Rep.");
        } else {
            Response::error("Failed to assign rep.", 500);
        }
    }
}
?>
