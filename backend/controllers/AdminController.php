<?php
namespace Controllers;
use Models\UserFactory;
use Middleware\AuthMiddleware;

class AdminController {
    public function handleUsers($method, $id = null) {
        $decoded = AuthMiddleware::authenticate(['admin']);
        $admin = UserFactory::create('admin');
        $db = \Config\Database::getInstance()->getConnection();
        
        if ($method === 'GET') {
            $q = $_GET['q'] ?? '';
            $role = $_GET['role'] ?? 'all';
            if ($role === '') $role = 'all'; // Fix empty string from frontend filter
            
            // Map frontend role 'rep' to backend 'course_representative'
            if ($role === 'rep') $role = 'course_representative';
            
            $sql = "SELECT 
                        u.userID as id, 
                        u.fname as first_name, 
                        u.lname as last_name, 
                        u.email, 
                        u.role, 
                        u.is_active,
                        s.enrollmentNo as enrollment_no,
                        st.staffID as staff_id
                    FROM users u
                    LEFT JOIN student s ON u.userID = s.userID
                    LEFT JOIN staff st ON u.userID = st.userID
                    WHERE 1=1";
            $params = [];
            
            if ($role !== 'all') {
                $sql .= " AND u.role = :role";
                $params[':role'] = $role;
            }
            if (!empty($q)) {
                $sql .= " AND (u.fname LIKE :q OR u.lname LIKE :q OR u.email LIKE :q)";
                $params[':q'] = "%$q%";
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Map course_representative back to rep for frontend
            foreach ($users as &$u) {
                if ($u['role'] === 'course_representative') {
                    $u['role'] = 'rep';
                }
            }
            
            file_put_contents('admin_users_debug.txt', json_encode(['role' => $role, 'q' => $q, 'users' => $users]));
            
            echo json_encode(['success' => true, 'data' => $users]);
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
            $user = UserFactory::create($role, $data);
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
        $db = \Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE users SET is_active = :status WHERE userID = :uid");
        $success = $stmt->execute([':status' => $isActive, ':uid' => $id]);
        
        echo json_encode(['success' => $success]);
    }
    
    public function searchStudents() {
        AuthMiddleware::authenticate(['admin']);
        $q = $_GET['q'] ?? '';
        $db = \Config\Database::getInstance()->getConnection();
        
        $sql = "SELECT u.userID as id, u.fname as first_name, u.lname as last_name, u.email, s.enrollmentNo as enrollment_no, s.courseID as course_id
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
        $db = \Config\Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            // Update role to course_representative
            $db->prepare("UPDATE users SET role = 'course_representative' WHERE userID = ?")->execute([$data['user_id']]);
            
            // Insert into course_representative
            $sql = "INSERT INTO course_representative (userID, enrollmentNo, courseID, rep_id_string) 
                    VALUES (?, ?, ?, ?)";
            // Mock courseID = 1 if missing for now
            $db->prepare($sql)->execute([
                $data['user_id'], 
                $data['email'], // Using email as temp enrollment string fallback if no enrollment table
                1, 
                $data['rep_id']
            ]);
            
            $db->commit();
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to assign rep: ' . $e->getMessage()]);
        }
    }
    
    public function moderateContent() {
        AuthMiddleware::authenticate(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);
        $db = \Config\Database::getInstance()->getConnection();
        
        // This is a minimal mock for content moderation since table structure is variable.
        if ($data['content_type'] === 'Lost Item') {
            if ($data['status'] === 'removed') {
                $db->prepare("DELETE FROM lost_items WHERE lostID = ?")->execute([$data['content_id']]);
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
        $db = \Config\Database::getInstance()->getConnection();
        
        $stats = [
            'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'active_users' => $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
            'total_reps' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'course_representative'")->fetchColumn(),
            'total_posts' => $db->query("SELECT (SELECT COUNT(*) FROM lost_items) + (SELECT COUNT(*) FROM notes) + (SELECT COUNT(*) FROM marketplace)")->fetchColumn(),
            'hidden_posts' => 0, // Mock for now
            'recent_logs' => [] // Mock empty array so frontend map doesn't crash
        ];
        
        echo json_encode(['success' => true, 'data' => $stats]);
    }
    
    public function getContent() {
        AuthMiddleware::authenticate(['admin']);
        $db = \Config\Database::getInstance()->getConnection();
        
        $content = [
            'lost_items' => [],
            'marketplace' => [],
            'notes' => []
        ];
        
        $stmt = $db->query("SELECT l.lostID as lost_id, l.lostItemName, u.email, l.created_at, l.status 
                            FROM lost_items l 
                            JOIN users u ON l.userID = u.userID 
                            ORDER BY l.created_at DESC LIMIT 50");
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($items as $item) {
            $item['status'] = $item['status'] ?: 'active';
            $content['lost_items'][] = $item;
        }
        
        echo json_encode(['success' => true, 'data' => $content]);
    }
    
    public function getReports() {
        AuthMiddleware::authenticate(['admin']);
        // Mock reports
        echo json_encode(['success' => true, 'data' => []]);
    }
}
