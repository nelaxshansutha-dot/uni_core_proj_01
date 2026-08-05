<?php
namespace Controllers;

use Middleware\AuthMiddleware;

class AdminController {
    public function handleUsers($method, $id = null) {
        AuthMiddleware::authenticate(['admin']);
        
        if ($method === 'GET') {
            try {
                $q = $_GET['q'] ?? '';
                $role = $_GET['role'] ?? 'all';
                if ($role === '') $role = 'all'; 
                
                $admin = new \Models\Admin();
                $users = $admin->manageUsers($role, $q);
                
                echo json_encode(['success' => true, 'data' => $users]);
            } catch (\Exception $e) {
                error_log("handleUsers error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'An error occurred while fetching users.']);
            }
            return;
        }
    }
        
    public function createUser() {
        AuthMiddleware::authenticate(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['email'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid payload']);
            return;
        }

        $role = $data['role'] ?? 'student';
        $data['hash_password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Map frontend fields to backend model fields
        $data['fname'] = $data['first_name'];
        $data['lname'] = $data['last_name'];
        $data['phoneNum'] = $data['phone_number'];
        $data['enrollmentNo'] = $data['enrollment_no']; // Student specific
        $data['courseID'] = isset($data['course']) && !empty($data['course']) ? $data['course'] : 1; // Default to 1 if empty
        $data['std_year'] = isset($data['year']) && !empty($data['year']) ? $data['year'] : 1;
        
        try {
            switch ($role) {
                case 'admin': $user = new \Models\Admin($data); break;
                case 'staff': $user = new \Models\Staff($data); break;
                case 'course_representative': $user = new \Models\CourseRepresentative($data); break;
                case 'student':
                default: $user = new \Models\Student($data); break;
            }
            $userID = $user->register();
            
            // Auto verify admin created users
            $db = \Config\Database::getInstance()->getConnection();
            $db->prepare("UPDATE users SET is_verified = 1 WHERE userID = ?")->execute([$userID]);
            
            echo json_encode(['success' => true, 'message' => 'User created successfully']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateUser($id) {
        AuthMiddleware::authenticate(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);
        $db = \Config\Database::getInstance()->getConnection();
        
        $sql = "UPDATE users SET fname = :fname, lname = :lname, phoneNum = :phone, email = :email WHERE userID = :uid";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([
            ':fname' => $data['first_name'],
            ':lname' => $data['last_name'],
            ':phone' => $data['phone_number'],
            ':email' => $data['email'],
            ':uid' => $id
        ]);
        
        if ($success) echo json_encode(['success' => true]);
        else echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }

    public function toggleUserStatus($id) {
        AuthMiddleware::authenticate(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Allow un-assigning reps
        if (strpos($id, 'rep_') === 0) {
            $uid = str_replace('rep_', '', $id);
            $db = \Config\Database::getInstance()->getConnection();
            $db->prepare("UPDATE users SET role = 'student' WHERE userID = ?")->execute([$uid]);
            $db->prepare("DELETE FROM course_representative WHERE userID = ?")->execute([$uid]);
            echo json_encode(['success' => true]);
            return;
        }

        $isActive = isset($data['is_active']) && $data['is_active'] ? 1 : 0;
        $reason = $data['reason'] ?? 'No reason provided by administrator.';
        $db = \Config\Database::getInstance()->getConnection();
        
        if ($isActive === 0) {
            $stmt = $db->prepare("SELECT email FROM users WHERE userID = :uid");
            $stmt->execute([':uid' => $id]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['email'])) {
                require_once __DIR__ . '/../utils/MailService.php';
                \Utils\MailService::sendDeactivationEmail($user['email'], $reason);
            }
        }
        
        $stmt = $db->prepare("UPDATE users SET is_active = :status WHERE userID = :uid");
        $success = $stmt->execute([':status' => $isActive, ':uid' => $id]);
        
        echo json_encode(['success' => $success]);
    }
    
    public function searchStudents() {
        AuthMiddleware::authenticate(['admin']);
        $q = $_GET['q'] ?? '';
        $db = \Config\Database::getInstance()->getConnection();
        
        $sql = "SELECT u.userID as id, u.fname as first_name, u.lname as last_name, u.email, u.phoneNum as phone_number, u.role, s.enrollmentNo as enrollment_no, s.courseID as course, s.std_year as year
                FROM users u 
                JOIN student s ON u.userID = s.userID 
                WHERE u.role = 'student' AND (s.enrollmentNo LIKE :q OR u.fname LIKE :q OR u.email LIKE :q)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':q' => "%$q%"]);
        $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $students]);
    }

    public function assignCourseRep() {
        AuthMiddleware::authenticate(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);
        
        try {
            $admin = new \Models\Admin();
            $result = $admin->assignCourseRep($data);
            echo json_encode($result);
        } catch (\Exception $e) {
            error_log("assignCourseRep error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while assigning rep.']);
        }
    }
    
    public function moderateContent() {
        AuthMiddleware::authenticate(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);
        $db = \Config\Database::getInstance()->getConnection();
        
        $contentType = $data['content_type'] ?? '';
        $contentId = $data['content_id'] ?? null;
        $status = $data['status'] ?? '';
        $reason = $data['reason'] ?? '';

        if ($status === 'removed' && $contentId) {
            $ownerEmail = null;
            $title = '';

            if ($contentType === 'lost_item') {
                $stmt = $db->prepare("SELECT u.email, l.lostItemName as title FROM lost_items l JOIN users u ON l.userID = u.userID WHERE l.lostID = ?");
                $stmt->execute([$contentId]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    $ownerEmail = $row['email'];
                    $title = $row['title'];
                }
                $db->prepare("DELETE FROM lost_items WHERE lostID = ?")->execute([$contentId]);

            } elseif ($contentType === 'marketplace') {
                $stmt = $db->prepare("SELECT u.email, m.productName as title FROM marketplace m JOIN users u ON m.userID = u.userID WHERE m.productID = ?");
                $stmt->execute([$contentId]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    $ownerEmail = $row['email'];
                    $title = $row['title'];
                }
                $db->prepare("DELETE FROM marketplace WHERE productID = ?")->execute([$contentId]);

            } elseif ($contentType === 'notes') {
                $stmt = $db->prepare("SELECT u.email, n.title FROM notes n JOIN student s ON n.enrollmentNo = s.enrollmentNo JOIN users u ON s.userID = u.userID WHERE n.noteID = ?");
                $stmt->execute([$contentId]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    $ownerEmail = $row['email'];
                    $title = $row['title'];
                }
                $db->prepare("DELETE FROM notes WHERE noteID = ?")->execute([$contentId]);
            }

            // Send notification email to content owner
            if ($ownerEmail) {
                try {
                    \Utils\MailService::sendModerationEmail($ownerEmail, $title, $contentType, $reason);
                } catch (\Exception $e) {
                    error_log("Failed to send moderation email: " . $e->getMessage());
                }
            }
        }
        
        echo json_encode(['success' => true]);
    }

    public function moderateReport() {
        AuthMiddleware::authenticate(['admin']);
        echo json_encode(['success' => true]);
    }

    public function getDashboardStats() {
        AuthMiddleware::authenticate(['admin']);
        try {
            $admin = new \Models\Admin();
            $monitorData = $admin->monitorPlatform();
            echo json_encode(['success' => true, 'data' => $monitorData['stats']]);
        } catch (\Exception $e) {
            error_log("getDashboardStats error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while fetching stats.']);
        }
    }
    
    public function getContent() {
        AuthMiddleware::authenticate(['admin']);
        try {
            $admin = new \Models\Admin();
            $monitorData = $admin->monitorPlatform();
            echo json_encode(['success' => true, 'data' => $monitorData['content']]);
        } catch (\Exception $e) {
            error_log("getContent error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while fetching content.']);
        }
    }
    
    public function getReports() {
        AuthMiddleware::authenticate(['admin']);
        try {
            $admin = new \Models\Admin();
            $monitorData = $admin->monitorPlatform();
            echo json_encode(['success' => true, 'data' => $monitorData['reports']]);
        } catch (\Exception $e) {
            error_log("getReports error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while fetching reports.']);
        }
    }
}
