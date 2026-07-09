<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Staff.php';
require_once __DIR__ . '/../models/CourseRep.php';
require_once __DIR__ . '/../models/LostItem.php';
require_once __DIR__ . '/../models/Marketplace.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/MailService.php';

require_once __DIR__ . '/BaseController.php';

class AdminController extends BaseController {

    public function getDashboardStats() {
        $userModel = new User();
        $lostItemModel = new LostItem();
        $marketplaceModel = new Marketplace();

        $stats = [
            'totalUsers' => (int)$userModel->countAll(),
            'activeUsers' => (int)$userModel->countVerified(),
            'totalReps' => (int)$userModel->countReps(),
            'lostCount' => (int)$lostItemModel->countAll(),
            'marketCount' => (int)$marketplaceModel->countAll(),
            'notesCount' => 0
        ];
        
        $stats['hidden_posts'] = 0;
        $stats['recent_logs'] = [];
        $stats['active_posts'] = $stats['lostCount'] + $stats['marketCount'] + $stats['notesCount'];
        $stats['total_posts'] = $stats['active_posts'];
        $stats['deactivated_users'] = $stats['totalUsers'] - $stats['activeUsers'];

        Response::success("Stats retrieved", [
            'total_users' => $stats['totalUsers'],
            'active_users' => $stats['activeUsers'],
            'deactivated_users' => $stats['deactivated_users'],
            'total_reps' => $stats['totalReps'],
            'total_posts' => $stats['total_posts'],
            'active_posts' => $stats['active_posts'],
            'hidden_posts' => $stats['hidden_posts'],
            'recent_logs' => $stats['recent_logs']
        ]);
    }

    public function getUsers($query = '', $role = '') {
        $userModel = new User();
        $allUsers = $userModel->getAllWithDetails($query, $role);
        Response::success("Users retrieved", $allUsers);
    }

    public function createUser($data, $adminId) {
        Validator::validateRequired(['enrollment_no', 'email', 'password', 'role', 'first_name', 'last_name'], $data);

        if (!Validator::validateName($data['first_name']) || !Validator::validateName($data['last_name'])) {
            Response::error("First name and last name must contain only letters and spaces.");
        }

        $userModel = new User();
        if ($userModel->findByEnrollment($data['enrollment_no']) || $userModel->findByEmail($data['email'])) {
            Response::error("Enrollment number or email is already registered.");
        }

        $userData = [
            'fname' => $data['first_name'],
            'lname' => $data['last_name'],
            'email' => $data['email'],
            'phoneNum' => null,
            'hash_password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role' => $data['role']
        ];

        $user_id = $userModel->create($userData);

        if ($user_id) {
            $userModel->markAsVerified($user_id);

            if ($data['role'] === 'student' || $data['role'] === 'rep') {
                $studentModel = new Student();
                $courseID = !empty($data['course']) ? $data['course'] : Student::extractCourseFromEnrollment($data['enrollment_no']);
                
                $studentModel->create([
                    'userID' => $user_id,
                    'enrollmentNo' => $data['enrollment_no'],
                    'courseID' => $courseID,
                    'std_year' => !empty($data['year']) ? $data['year'] : null
                ]);
            } else if ($data['role'] === 'staff') {
                $staffModel = new Staff();
                $staffModel->create([
                    'userID' => $user_id,
                    'staffID' => $data['enrollment_no'] ?? null
                ]);
            }
            Response::success("User created successfully.");
        } else {
            Response::error("Failed to create user.", 500);
        }
    }

    public function updateUser($id, $data, $adminId) {
        Validator::validateRequired(['email', 'first_name', 'last_name'], $data);

        if (!Validator::validateName($data['first_name']) || !Validator::validateName($data['last_name'])) {
            Response::error("First name and last name must contain only letters and spaces.");
        }

        // Handle rep_ prefix if they edit the rep row
        $isRepRow = strpos((string)$id, 'rep_') === 0;
        $realId = $isRepRow ? (int)str_replace('rep_', '', $id) : (int)$id;

        $userModel = new User();
        $role = $userModel->getRole($realId);
        if (!$role) {
            Response::error("User not found", 404);
        }

        $db = (new Database())->getConnection();
        $db->beginTransaction();

        try {
            // 1. Update Core User Info
            $userModel->updateProfile($realId, $data);

            // 2. Update Role-Specific Info
            if ($role === User::ROLE_STAFF) {
                $staffModel = new Staff();
                $staffModel->updateAdminProfile($realId);
            } else if ($role === User::ROLE_STUDENT || $role === User::ROLE_REP) {
                require_once __DIR__ . '/../models/Student.php';
                $studentModel = new Student();
                
                $enrollmentNo = isset($data['enrollment_no']) ? $data['enrollment_no'] : null;
                $courseID = isset($data['course']) ? $data['course'] : null;
                $std_year = isset($data['year']) ? $data['year'] : null;
                
                $studentModel->updateAdminProfile($realId, $enrollmentNo, $courseID, $std_year);
            }

            $db->commit();
            Response::success("User updated successfully.");
        } catch (Exception $e) {
            $db->rollBack();
            Response::error("Failed to update user: " . $e->getMessage(), 500);
        }
    }

    public function toggleUserStatus($id, $data, $adminId) {
        if (!isset($data['is_active'])) {
            Response::error('Missing is_active flag');
        }
        $isActive = $data['is_active'] ? 1 : 0;
        $userModel = new User();
        $realId = (int)str_replace('rep_', '', $id);
        if (strpos((string)$id, 'rep_') === 0) {
            $courseRepModel = new CourseRep();
            $courseRepModel->toggleStatus($realId, $isActive);
        } else {
            $userModel->toggleStatus($realId, $isActive);
        }
        if ($isActive === 0 && !empty($data['reason'])) {
            $user = $userModel->findById($realId);
            if ($user && !empty($user['email'])) {
                MailService::sendDeactivationEmail($user['email'], $data['reason']);
            }
        }
        Response::success('User status updated successfully.');
    }

    public function searchStudents($query) {
        $userModel = new User();
        $students = $userModel->searchStudents($query);
        Response::success('Students found', $students);
    }

    public function assignRep($data, $adminId) {
        Validator::validateRequired(['user_id', 'fname', 'lname', 'email', 'rep_id', 'password'], $data);

        if (!Validator::validateName($data['fname']) || !Validator::validateName($data['lname'])) {
            Response::error("First name and last name must contain only letters and spaces.");
        }

        $userModel = new User();
        
        $student = $userModel->getStudentDetails($data['user_id']);

        if (!$student) {
            Response::error("Student not found.");
        }

        try {
            $courseRepModel = new CourseRep();
            $courseRepModel->assignRep($data, $student);

            // Generate PDF
            // Send Email via MailService
            MailService::sendRepCredentialEmail($data['email'], $data['fname'], $data['lname'], $data['rep_id'], $data['password']);

            Response::success("Successfully assigned student as Course Representative and sent credential email.");
        } catch (Throwable $e) {
            file_put_contents(__DIR__ . '/../admin_log.txt', "AssignRep Exception: " . $e->getMessage() . "\n", FILE_APPEND);
            Response::error("Failed to assign Rep: " . $e->getMessage(), 500);
        }
    }

    public function getContent($type = '') {
        $content = [
            'lost_items' => [],
            'marketplace' => [],
            'notes' => []
        ];

        if (empty($type) || $type === 'lost_item') {
            $content['lost_items'] = (new LostItem())->getAdminContent();
        }
        if (empty($type) || $type === 'marketplace') {
            $content['marketplace'] = (new Marketplace())->getAdminContent();
        }

        Response::success("Content retrieved", $content);
    }

    public function updateContentStatus($data, $adminId) {
        if (!isset($data['content_type']) || !isset($data['content_id']) || !isset($data['status'])) {
            Response::error("Missing required fields.", 400);
        }

        $type = $data['content_type'];
        $id = (int)$data['content_id'];
        $status = $data['status'];
        
        try {
            $db = (new Database())->getConnection();
            $userEmail = '';
            $itemTitle = '';
            
            if ($type === 'lost_item') {
                $stmt = $db->prepare("SELECT u.email, l.lostItemName as item_name FROM lost_items l JOIN users u ON l.userID = u.userID WHERE l.lostID = ?");
                $stmt->execute([$id]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($res) { $userEmail = $res['email']; $itemTitle = $res['item_name']; }
                
                (new LostItem())->updateAdminStatus($id, $status);
            } else if ($type === 'marketplace') {
                $stmt = $db->prepare("SELECT u.email, m.productName as product_name FROM marketplace m JOIN users u ON m.userID = u.userID WHERE m.productID = ?");
                $stmt->execute([$id]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($res) { $userEmail = $res['email']; $itemTitle = $res['product_name']; }
                
                (new Marketplace())->updateAdminStatus($id, $status);
            } else if ($type === 'notes') {
                $stmt = $db->prepare("SELECT u.email, n.title FROM notes n JOIN student s ON n.enrollmentNo = s.enrollmentNo JOIN users u ON s.userID = u.userID WHERE n.noteID = ?");
                $stmt->execute([$id]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($res) { $userEmail = $res['email']; $itemTitle = $res['title']; }
                
                $updateStmt = $db->prepare("UPDATE notes SET status = ? WHERE noteID = ?");
                $updateStmt->execute([$status, $id]);
            } else {
                Response::error("Invalid content type.");
            }

            if ($status === 'removed' && !empty($userEmail) && isset($data['reason'])) {
                require_once __DIR__ . '/../utils/MailService.php';
                MailService::sendContentDeletionEmail($userEmail, $type, $itemTitle, $data['reason']);
            }

            Response::success("Content status updated successfully.");
        } catch (Exception $e) {
            Response::error("Failed to update content status: " . $e->getMessage(), 500);
        }
    }

    public function getReports() {
        Response::success("Reports retrieved", []);
    }

    public function updateReportStatus($data, $adminId) {
        Response::success("Report status updated successfully.");
    }
}
?>
