<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/Database.php';

require_once __DIR__ . '/BaseController.php';

class ProfileController extends BaseController {
    public function getProfile($userId) {
        $db = (new Database())->getConnection();
        
        $stmt = $db->prepare("SELECT u.userID as id, s.enrollmentNo as enrollment_no, u.fname as first_name, u.lname as last_name, u.email, u.phoneNum as phone_number, u.lost_item_sms_notification, u.peer_learning_app_notification, u.has_seen_lost_item_popup, u.role FROM Users u LEFT JOIN Student s ON u.userID = s.userID WHERE u.userID = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Response::error("User not found", 404);
        }

        $profile = null;
        if ($user['role'] === 'student') {
            $stmt = $db->prepare("SELECT courseID, std_year as year, enrollmentNo as enrollment_no FROM Student WHERE userID = ?");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        } else if ($user['role'] === 'rep') {
            $stmt = $db->prepare("SELECT s.courseID, s.std_year as year, c.rep_id_string as enrollment_no FROM Student s JOIN Course_representative c ON s.userID = c.userID WHERE s.userID = ?");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        } else if ($user['role'] === 'staff') {
            $stmt = $db->prepare("SELECT staffID as enrollment_no FROM Staff WHERE userID = ?");
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
        $peerPref = isset($data['peer_learning_app_notification']) ? (int)$data['peer_learning_app_notification'] : 0;
        $phoneNumber = isset($data['phone_number']) ? $data['phone_number'] : null;

        // Update main table
        $stmt = $db->prepare("UPDATE Users SET phoneNum = ?, lost_item_sms_notification = ?, peer_learning_app_notification = ? WHERE userID = ?");
        $stmt->execute([$phoneNumber, $smsPref, $peerPref, $userId]);

        // Update role specific details
        if (isset($data['first_name']) && isset($data['last_name'])) {
            $firstName = $data['first_name'];
            $lastName = $data['last_name'];
            
            $stmt = $db->prepare("UPDATE Users SET fname = ?, lname = ? WHERE userID = ?");
            $stmt->execute([$firstName, $lastName, $userId]);
            
            $stmtRole = $db->prepare("SELECT role FROM Users WHERE userID = ?");
            $stmtRole->execute([$userId]);
            $user = $stmtRole->fetch(PDO::FETCH_ASSOC);

            if ($user['role'] === 'student' || $user['role'] === 'rep') {
                $course = isset($data['course']) ? $data['course'] : null;
                $year = isset($data['year']) ? (int)$data['year'] : null;
                $stmt = $db->prepare("UPDATE Student SET courseID = ?, std_year = ? WHERE userID = ?");
                $stmt->execute([$course, $year, $userId]);
            } else if ($user['role'] === 'staff') {
                // No specific details to update for staff
            }
        }

        Response::success("Profile updated successfully.");
    }
}
?>
