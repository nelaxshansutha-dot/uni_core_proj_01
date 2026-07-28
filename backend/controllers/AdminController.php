<?php
namespace Controllers;

use Middleware\AuthMiddleware;

class AdminController {
    public function handleUsers($method, $id = null) {
        $decoded = AuthMiddleware::authenticate(['admin']);
        $admin = new \Models\Admin();
        $db = \Config\Database::getInstance()->getConnection();
        
        if ($method === 'GET') {
            $q = $_GET['q'] ?? '';
            $role = $_GET['role'] ?? 'all';
            if ($role === '') $role = 'all'; 
            
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
        $db = \Config\Database::getInstance()->getConnection();
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
        $db = \Config\Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();

            $userID = $data['user_id'];

            // Get student's current record
            $stmt = $db->prepare("SELECT enrollmentNo, courseID FROM student WHERE userID = ?");
            $stmt->execute([$userID]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);
            $enrollmentNo = $student ? $student['enrollmentNo'] : null;

            if (!$enrollmentNo) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Student enrollment number not found.']);
                return;
            }

            // Auto-detect courseID from enrollment number (e.g. UWU/CST/23/088 → CST → courseID 1)
            $courseID = $student['courseID'] ?? null;
            $enrUpper = strtoupper(trim($enrollmentNo));
            $enrParts = explode('/', $enrUpper);
            if (!$courseID) {
                $courseCode = $enrParts[1] ?? '';
                if ($courseCode === 'CST') $courseID = 1;
            }

            // Auto-detect academic year from batch year in enrollment number
            $batchYear = isset($enrParts[2]) ? (int)$enrParts[2] : null;
            $currentYear = (int)date('Y');
            $currentMonth = (int)date('m');
            $academicYear = ($currentMonth < 10) ? $currentYear - 1 : $currentYear;
            $stdYear = $batchYear ? ($academicYear % 100 - $batchYear) : null;

            // Update student's courseID and year if not set
            if ($courseID) {
                $db->prepare("UPDATE student SET courseID = :cid, std_year = COALESCE(std_year, :yr) WHERE userID = :uid")
                   ->execute([':cid' => $courseID, ':yr' => $stdYear, ':uid' => $userID]);
            }

            // Do NOT promote user role to course_representative here.
            // We want to keep them as 'student' in the users table so they can
            // login to both their student dashboard and rep dashboard independently.
            // Rep ID: use provided rep_id or auto-generate as rep_ + lowercase enrollment
            $repId = !empty($data['rep_id']) 
                ? strtolower(trim($data['rep_id'])) 
                : 'rep_' . strtolower($enrollmentNo);

            $hashPass = password_hash($data['password'], PASSWORD_BCRYPT);

            // Check if already a rep (update) or new (insert)
            $existCheck = $db->prepare("SELECT repID FROM course_representative WHERE userID = ?");
            $existCheck->execute([$userID]);
            $existRep = $existCheck->fetch(\PDO::FETCH_ASSOC);

            if ($existRep) {
                // Update existing rep record
                $db->prepare("UPDATE course_representative SET rep_id_string = ?, hash_password = ?, courseID = ? WHERE userID = ?")
                   ->execute([$repId, $hashPass, $courseID, $userID]);
            } else {
                // Insert new rep record
                $db->prepare(
                    "INSERT INTO course_representative (userID, enrollmentNo, courseID, rep_id_string, hash_password) VALUES (?, ?, ?, ?, ?)"
                )->execute([$userID, $enrollmentNo, $courseID, $repId, $hashPass]);
            }

            // Send credentials email
            require_once __DIR__ . '/../utils/MailService.php';
            \Utils\MailService::sendRepCredentialEmail(
                $data['email']  ?? '',
                $data['fname']  ?? '',
                $data['lname']  ?? '',
                $repId,
                $data['password']
            );
            
            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => "Successfully assigned {$enrollmentNo} as Course Representative (Rep ID: {$repId})."
            ]);
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
