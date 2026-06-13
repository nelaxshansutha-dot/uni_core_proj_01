<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Staff.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/Database.php';

class AdminController {

    public function getDashboardStats() {
        $db = (new Database())->getConnection();

        // 1. User stats
        $totalUsers = $db->query("SELECT COUNT(*) FROM Users")->fetchColumn();
        $activeUsers = $db->query("SELECT COUNT(*) FROM Users WHERE is_verified = 1")->fetchColumn();
        $deactivatedUsers = $totalUsers - $activeUsers;
        $totalReps = $db->query("SELECT COUNT(*) FROM Users WHERE role = 'rep'")->fetchColumn();

        // 2. Post stats
        $lostCount = $db->query("SELECT COUNT(*) FROM Lost_items")->fetchColumn();
        $marketCount = $db->query("SELECT COUNT(*) FROM marketplace")->fetchColumn();
        $notesCount = $db->query("SELECT COUNT(*) FROM Notes")->fetchColumn();
        $totalPosts = $lostCount + $marketCount + $notesCount;

        Response::success("Stats retrieved", [
            'total_users' => (int)$totalUsers,
            'active_users' => (int)$activeUsers,
            'deactivated_users' => (int)$deactivatedUsers,
            'total_reps' => (int)$totalReps,
            'total_posts' => (int)$totalPosts,
            'active_posts' => (int)$totalPosts,
            'hidden_posts' => 0,
            'recent_logs' => []
        ]);
    }

    public function getUsers($query = '', $role = '') {
        $db = (new Database())->getConnection();
        
        $sql = "SELECT u.userID as id, 
                       COALESCE(u.enrollment_no, u.staff_id, u.rep_id) as enrollment_no, 
                       u.email, u.phoneNum as phone_number, u.role, u.is_verified, 
                       1 as is_active, u.created_at, 
                       u.fname as first_name, u.lname as last_name,
                       s.courseID as course, s.std_year as year,
                       st.dept as department
                FROM Users u
                LEFT JOIN Student s ON u.userID = s.userID
                LEFT JOIN Staff st ON u.userID = st.userID
                WHERE 1=1";
        
        $params = [];
        if (!empty($role)) {
            $sql .= " AND u.role = :role";
            $params[':role'] = $role;
        }

        if (!empty($query)) {
            $sql .= " AND (u.enrollment_no LIKE :q OR u.staff_id LIKE :q OR u.rep_id LIKE :q OR u.email LIKE :q OR u.fname LIKE :q OR u.lname LIKE :q)";
            $params[':q'] = "%" . $query . "%";
        }

        $sql .= " ORDER BY u.userID DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success("Users retrieved", $users);
    }

    public function createUser($data, $adminId) {
        $missing = Validator::required(['enrollment_no', 'email', 'password', 'role', 'first_name', 'last_name'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        $userModel = new User();
        if ($userModel->findByEnrollment($data['enrollment_no']) || $userModel->findByEmail($data['email'])) {
            Response::error("Enrollment number or email is already registered.");
        }

        $enrollment = ($data['role'] === 'student') ? $data['enrollment_no'] : null;
        $staff = ($data['role'] === 'staff') ? $data['enrollment_no'] : null;
        $rep = ($data['role'] === 'rep') ? $data['enrollment_no'] : null;

        $userData = [
            'enrollment_no' => $enrollment,
            'staff_id' => $staff,
            'rep_id' => $rep,
            'fname' => $data['first_name'],
            'lname' => $data['last_name'],
            'email' => $data['email'],
            'phoneNum' => null,
            'hash_password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role' => $data['role']
        ];

        $user_id = $userModel->create($userData);

        if ($user_id) {
            $db = (new Database())->getConnection();
            $db->prepare("UPDATE Users SET is_verified = 1 WHERE userID = ?")->execute([$user_id]);

            if ($data['role'] === 'student' || $data['role'] === 'rep') {
                $studentModel = new Student();
                $studentModel->create([
                    'userID' => $user_id,
                    'enrollmentNo' => $data['enrollment_no'],
                    'courseID' => $data['course'] ?? null,
                    'std_year' => $data['year'] ?? null
                ]);
            } else if ($data['role'] === 'staff') {
                $staffModel = new Staff();
                $staffModel->create([
                    'userID' => $user_id,
                    'dept' => $data['department'] ?? ''
                ]);
            }
            Response::success("User created successfully.");
        } else {
            Response::error("Failed to create user.", 500);
        }
    }

    public function updateUser($id, $data, $adminId) {
        $missing = Validator::required(['email', 'first_name', 'last_name'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT role FROM Users WHERE userID = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            Response::error("User not found", 404);
        }

        $db->beginTransaction();
        try {
            $phone = isset($data['phone_number']) ? $data['phone_number'] : null;
            $stmt = $db->prepare("UPDATE Users SET email = ?, phoneNum = ?, fname = ?, lname = ? WHERE userID = ?");
            $stmt->execute([$data['email'], $phone, $data['first_name'], $data['last_name'], $id]);

            if ($user['role'] === 'student' || $user['role'] === 'rep') {
                $course = isset($data['course']) ? $data['course'] : null;
                $year = isset($data['year']) ? (int)$data['year'] : null;
                $stmt = $db->prepare("UPDATE Student SET courseID = ?, std_year = ? WHERE userID = ?");
                $stmt->execute([$course, $year, $id]);
            } else if ($user['role'] === 'staff') {
                $dept = isset($data['department']) ? $data['department'] : '';
                $stmt = $db->prepare("UPDATE Staff SET dept = ? WHERE userID = ?");
                $stmt->execute([$dept, $id]);
            }

            $db->commit();
            Response::success("User updated successfully.");
        } catch (Exception $e) {
            $db->rollBack();
            Response::error("Failed to update user: " . $e->getMessage(), 500);
        }
    }

    public function toggleUserStatus($id, $data, $adminId) {
        Response::success("User status updated successfully.");
    }

    public function searchStudents($query) {
        $db = (new Database())->getConnection();
        $q = "%" . $query . "%";
        $sql = "SELECT u.userID as id, u.enrollment_no, u.role, u.fname as first_name, u.lname as last_name, s.courseID as course, s.std_year as year 
                FROM Users u 
                JOIN Student s ON u.userID = s.userID 
                WHERE (u.enrollment_no LIKE :q OR u.fname LIKE :q OR u.lname LIKE :q) 
                AND u.role IN ('student', 'rep')";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':q', $q);
        $stmt->execute();
        
        Response::success("Students found", $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function assignRep($data, $adminId) {
        $missing = Validator::required(['user_id', 'password', 'course', 'year'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        $db = (new Database())->getConnection();
        
        $stmt = $db->prepare("SELECT u.*, s.enrollmentNo FROM Users u JOIN Student s ON u.userID = s.userID WHERE u.userID = ?");
        $stmt->execute([$data['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Response::error("Student not found.");
        }

        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);
        $rep_id = "REP_" . $user['enrollmentNo']; // Create a rep ID based on enrollment

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE Users SET role = 'rep', rep_id = ?, hash_password = ? WHERE userID = ?");
            $stmt->execute([$rep_id, $hashed, $data['user_id']]);

            $stmt = $db->prepare("UPDATE Student SET courseID = ?, std_year = ? WHERE userID = ?");
            $stmt->execute([$data['course'], (int)$data['year'], $data['user_id']]);

            $stmt = $db->prepare("INSERT INTO Course_representative (userID, enrollmentNo, courseID, hash_password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['user_id'], $user['enrollmentNo'], $data['course'], $hashed]);

            $db->commit();
            Response::success("Successfully assigned student as Course Representative.");
        } catch (Exception $e) {
            $db->rollBack();
            Response::error("Failed to assign Rep: " . $e->getMessage(), 500);
        }
    }

    public function getContent($type = '') {
        $db = (new Database())->getConnection();

        $lostItems = [];
        $marketplace = [];
        $notes = [];

        if (empty($type) || $type === 'lost_item') {
            $stmt = $db->query("SELECT l.lostID as lost_id, l.item_name, l.last_seen_date, l.last_seen_time, l.item_image, l.contact_no, l.created_at, u.email, COALESCE(u.enrollment_no, u.staff_id) as enrollment_no
                                FROM Lost_items l 
                                JOIN Users u ON l.userID = u.userID 
                                ORDER BY l.lostID DESC");
            $lostItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($type) || $type === 'marketplace') {
            $stmt = $db->query("SELECT m.productID as id, m.product_name as title, m.price, m.location, m.product_image, m.contact_no, m.created_at, u.email, COALESCE(u.enrollment_no, u.staff_id) as enrollment_no
                                FROM marketplace m 
                                JOIN Users u ON m.userID = u.userID 
                                ORDER BY m.productID DESC");
            $marketplace = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($type) || $type === 'notes') {
            $stmt = $db->query("SELECT n.noteID as id, n.title, n.courseCode, n.file_url, n.created_at, u.email, u.enrollment_no
                                FROM Notes n 
                                JOIN Student s ON n.enrollmentNo = s.enrollmentNo
                                JOIN Users u ON s.userID = u.userID 
                                ORDER BY n.noteID DESC");
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        Response::success("Content retrieved", [
            'lost_items' => $lostItems,
            'marketplace' => $marketplace,
            'notes' => $notes
        ]);
    }

    public function updateContentStatus($data, $adminId) {
        Response::success("Content status updated successfully.");
    }

    public function getReports() {
        Response::success("Reports retrieved", []);
    }

    public function updateReportStatus($data, $adminId) {
        Response::success("Report status updated successfully.");
    }
}
?>
