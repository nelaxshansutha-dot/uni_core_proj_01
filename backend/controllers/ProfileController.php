<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/Database.php';

class ProfileController {
    public function getProfile($userId) {
        $db = (new Database())->getConnection();
        
        $stmt = $db->prepare("SELECT id, enrollment_no, email, phone_number, lost_item_sms_notification, has_seen_lost_item_popup, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Response::error("User not found", 404);
        }

        // Fetch profile data based on role
        $profile = null;
        if ($user['role'] === 'student' || $user['role'] === 'rep') {
            $stmt = $db->prepare("SELECT first_name, last_name, course, year FROM students WHERE user_id = ?");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        } else if ($user['role'] === 'staff') {
            $stmt = $db->prepare("SELECT first_name, last_name, department FROM staff WHERE user_id = ?");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($profile) {
            $user = array_merge($user, $profile);
        }

        Response::success("Profile retrieved", $user);
    }

    public function updateProfile($data, $userId) {
        $db = (new Database())->getConnection();

        // Update phone number and notifications
        $smsPref = isset($data['lost_item_sms_notification']) ? (int)$data['lost_item_sms_notification'] : 0;
        $phoneNumber = isset($data['phone_number']) ? $data['phone_number'] : null;

        // Update main table
        $stmt = $db->prepare("UPDATE users SET phone_number = ?, lost_item_sms_notification = ? WHERE id = ?");
        $stmt->execute([$phoneNumber, $smsPref, $userId]);

        // Update role specific details
        if (isset($data['first_name']) && isset($data['last_name'])) {
            $firstName = $data['first_name'];
            $lastName = $data['last_name'];
            
            $stmtRole = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmtRole->execute([$userId]);
            $user = $stmtRole->fetch(PDO::FETCH_ASSOC);

            if ($user['role'] === 'student' || $user['role'] === 'rep') {
                $course = isset($data['course']) ? $data['course'] : null;
                $year = isset($data['year']) ? (int)$data['year'] : null;
                $stmt = $db->prepare("UPDATE students SET first_name = ?, last_name = ?, course = ?, year = ? WHERE user_id = ?");
                $stmt->execute([$firstName, $lastName, $course, $year, $userId]);
            } else if ($user['role'] === 'staff') {
                $dept = isset($data['department']) ? $data['department'] : '';
                $stmt = $db->prepare("UPDATE staff SET first_name = ?, last_name = ?, department = ? WHERE user_id = ?");
                $stmt->execute([$firstName, $lastName, $dept, $userId]);
            }
        }

        Response::success("Profile updated successfully.");
    }
}
?>
