<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/Database.php';

class AdminController {

    private function logAction($adminId, $action, $targetType, $targetId, $details = null) {
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$adminId, $action, $targetType, $targetId, $details]);
        } catch (Exception $e) {
            // Silently fail logging if error
        }
    }

    public function getDashboardStats() {
        $db = (new Database())->getConnection();

        // 1. User stats
        $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $activeUsers = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
        $deactivatedUsers = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 0")->fetchColumn();
        $totalReps = $db->query("SELECT COUNT(*) FROM users WHERE role = 'rep'")->fetchColumn();

        // 2. Post stats
        $lostCount = $db->query("SELECT COUNT(*) FROM lost_items")->fetchColumn();
        $marketCount = $db->query("SELECT COUNT(*) FROM marketplace")->fetchColumn();
        $notesCount = $db->query("SELECT COUNT(*) FROM notes")->fetchColumn();
        $totalPosts = $lostCount + $marketCount + $notesCount;

        // 3. Active vs hidden posts
        $hiddenLost = $db->query("SELECT COUNT(*) FROM lost_items WHERE status = 'hidden'")->fetchColumn();
        $hiddenMarket = $db->query("SELECT COUNT(*) FROM marketplace WHERE status = 'hidden'")->fetchColumn();
        $hiddenNotes = $db->query("SELECT COUNT(*) FROM notes WHERE status = 'hidden'")->fetchColumn();
        $totalHidden = $hiddenLost + $hiddenMarket + $hiddenNotes;
        $totalActivePosts = $totalPosts - $totalHidden;

        // 4. Recent Admin Logs
        $stmt = $db->query("SELECT l.*, u.enrollment_no as admin_name 
                            FROM admin_logs l 
                            JOIN users u ON l.admin_id = u.id 
                            ORDER BY l.created_at DESC LIMIT 5");
        $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success("Stats retrieved", [
            'total_users' => (int)$totalUsers,
            'active_users' => (int)$activeUsers,
            'deactivated_users' => (int)$deactivatedUsers,
            'total_reps' => (int)$totalReps,
            'total_posts' => (int)$totalPosts,
            'active_posts' => (int)$totalActivePosts,
            'hidden_posts' => (int)$totalHidden,
            'recent_logs' => $recentLogs
        ]);
    }

    public function getUsers($query = '', $role = '') {
        $db = (new Database())->getConnection();
        
        $sql = "SELECT u.id, u.enrollment_no, u.email, u.phone_number, u.role, u.is_verified, u.is_active, u.created_at,
                       s.first_name as student_first, s.last_name as student_last, s.course, s.year,
                       st.first_name as staff_first, st.last_name as staff_last, st.department
                FROM users u
                LEFT JOIN students s ON u.id = s.user_id
                LEFT JOIN staff st ON u.id = st.user_id
                WHERE 1=1";
        
        $params = [];
        if (!empty($role)) {
            $sql .= " AND u.role = :role";
            $params[':role'] = $role;
        }

        if (!empty($query)) {
            $sql .= " AND (u.enrollment_no LIKE :q OR u.email LIKE :q OR s.first_name LIKE :q OR s.last_name LIKE :q OR st.first_name LIKE :q OR st.last_name LIKE :q)";
            $params[':q'] = "%" . $query . "%";
        }

        $sql .= " ORDER BY u.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format names nicely for frontend
        foreach ($users as &$u) {
            if ($u['role'] === 'student' || $u['role'] === 'rep') {
                $u['first_name'] = $u['student_first'];
                $u['last_name'] = $u['student_last'];
            } else if ($u['role'] === 'staff') {
                $u['first_name'] = $u['staff_first'];
                $u['last_name'] = $u['staff_last'];
            } else {
                $u['first_name'] = 'Admin';
                $u['last_name'] = 'User';
            }
            unset($u['student_first'], $u['student_last'], $u['staff_first'], $u['staff_last']);
        }

        Response::success("Users retrieved", $users);
    }

    public function createUser($data, $adminId) {
        $missing = Validator::required(['enrollment_no', 'email', 'password', 'role', 'first_name', 'last_name'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        $db = (new Database())->getConnection();
        
        // Check duplicate
        $stmt = $db->prepare("SELECT id FROM users WHERE enrollment_no = ? OR email = ?");
        $stmt->execute([$data['enrollment_no'], $data['email']]);
        if ($stmt->fetch()) {
            Response::error("Enrollment number or email is already registered.");
        }

        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO users (enrollment_no, email, password_hash, role, is_verified) VALUES (?, ?, ?, ?, TRUE)");
            $stmt->execute([$data['enrollment_no'], $data['email'], $hashed, $data['role']]);
            $userId = $db->lastInsertId();

            if ($data['role'] === 'student' || $data['role'] === 'rep') {
                $course = isset($data['course']) ? $data['course'] : null;
                $year = isset($data['year']) ? (int)$data['year'] : null;
                $stmt = $db->prepare("INSERT INTO students (user_id, first_name, last_name, course, year) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $data['first_name'], $data['last_name'], $course, $year]);
            } else if ($data['role'] === 'staff') {
                $dept = isset($data['department']) ? $data['department'] : '';
                $stmt = $db->prepare("INSERT INTO staff (user_id, first_name, last_name, department) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $data['first_name'], $data['last_name'], $dept]);
            }

            $db->commit();
            $this->logAction($adminId, "Created user", "users", $userId, "Enrollment: {$data['enrollment_no']}, Role: {$data['role']}");
            Response::success("User created successfully.");
        } catch (Exception $e) {
            $db->rollBack();
            Response::error("Failed to create user: " . $e->getMessage(), 500);
        }
    }

    public function updateUser($id, $data, $adminId) {
        $missing = Validator::required(['email', 'first_name', 'last_name'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        $db = (new Database())->getConnection();

        // Check if user exists
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            Response::error("User not found", 404);
        }

        $db->beginTransaction();
        try {
            $phone = isset($data['phone_number']) ? $data['phone_number'] : null;
            $stmt = $db->prepare("UPDATE users SET email = ?, phone_number = ? WHERE id = ?");
            $stmt->execute([$data['email'], $phone, $id]);

            if ($user['role'] === 'student' || $user['role'] === 'rep') {
                $course = isset($data['course']) ? $data['course'] : null;
                $year = isset($data['year']) ? (int)$data['year'] : null;
                $stmt = $db->prepare("UPDATE students SET first_name = ?, last_name = ?, course = ?, year = ? WHERE user_id = ?");
                $stmt->execute([$data['first_name'], $data['last_name'], $course, $year, $id]);
            } else if ($user['role'] === 'staff') {
                $dept = isset($data['department']) ? $data['department'] : '';
                $stmt = $db->prepare("UPDATE staff SET first_name = ?, last_name = ?, department = ? WHERE user_id = ?");
                $stmt->execute([$data['first_name'], $data['last_name'], $dept, $id]);
            }

            $db->commit();
            $this->logAction($adminId, "Updated user details", "users", $id);
            Response::success("User updated successfully.");
        } catch (Exception $e) {
            $db->rollBack();
            Response::error("Failed to update user: " . $e->getMessage(), 500);
        }
    }

    public function toggleUserStatus($id, $data, $adminId) {
        if (!isset($data['is_active'])) {
            Response::error("is_active status is required.");
        }

        $db = (new Database())->getConnection();
        $status = $data['is_active'] ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        if ($stmt->execute([$status, $id])) {
            $actionWord = $status ? "Activated" : "Deactivated";
            $this->logAction($adminId, "{$actionWord} user", "users", $id);
            Response::success("User status updated successfully.");
        } else {
            Response::error("Failed to update user status.", 500);
        }
    }

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

    public function assignRep($data, $adminId) {
        $missing = Validator::required(['user_id', 'password', 'course', 'year'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        $db = (new Database())->getConnection();
        
        // Find existing student
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$data['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Response::error("Student not found.");
        }

        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);

        $db->beginTransaction();
        try {
            // Update role & password in users
            $stmt = $db->prepare("UPDATE users SET role = 'rep', password_hash = ? WHERE id = ?");
            $stmt->execute([$hashed, $data['user_id']]);

            // Update course & year in students
            $stmt = $db->prepare("UPDATE students SET course = ?, year = ? WHERE user_id = ?");
            $stmt->execute([$data['course'], (int)$data['year'], $data['user_id']]);

            $db->commit();

            // Simulate PDF creation & email sending
            $pdfContent = "CREDENTIALS PDF\nEnrollment: {$user['enrollment_no']}\nRole: Course Representative\nCourse: {$data['course']}\nYear: {$data['year']}\nPassword: {$data['password']}";
            file_put_contents(__DIR__ . '/../admin_log.txt', "Assigned Course Representative to {$user['email']}.\nPDF Attachment:\n{$pdfContent}\n", FILE_APPEND);

            $this->logAction($adminId, "Promoted to Course Representative", "users", $data['user_id'], "Course: {$data['course']}, Year: {$data['year']}");
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
            $stmt = $db->query("SELECT l.*, u.enrollment_no, u.email 
                                FROM lost_items l 
                                JOIN users u ON l.user_id = u.id 
                                ORDER BY l.lost_id DESC");
            $lostItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($type) || $type === 'marketplace') {
            $stmt = $db->query("SELECT m.*, u.enrollment_no, u.email 
                                FROM marketplace m 
                                JOIN users u ON m.seller_id = u.id 
                                ORDER BY m.id DESC");
            $marketplace = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($type) || $type === 'notes') {
            $stmt = $db->query("SELECT n.*, u.enrollment_no, u.email 
                                FROM notes n 
                                JOIN users u ON n.uploader_id = u.id 
                                ORDER BY n.id DESC");
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        Response::success("Content retrieved", [
            'lost_items' => $lostItems,
            'marketplace' => $marketplace,
            'notes' => $notes
        ]);
    }

    public function updateContentStatus($data, $adminId) {
        $missing = Validator::required(['content_type', 'content_id', 'status'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        $db = (new Database())->getConnection();
        $table = '';
        $pk = 'id';
        
        if ($data['content_type'] === 'lost_item') {
            $table = 'lost_items';
            $pk = 'lost_id';
        } else if ($data['content_type'] === 'marketplace') {
            $table = 'marketplace';
        } else if ($data['content_type'] === 'notes') {
            $table = 'notes';
        } else {
            Response::error("Invalid content type.");
        }

        $isFlaggedSql = "";
        $params = [$data['status'], $data['content_id']];
        if (isset($data['is_flagged'])) {
            $isFlaggedSql = ", is_flagged = ?";
            $params = [$data['status'], (int)$data['is_flagged'], $data['content_id']];
        }

        $sql = "UPDATE {$table} SET status = ? {$isFlaggedSql} WHERE {$pk} = ?";
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute($params)) {
            $this->logAction($adminId, "Moderated content status to: " . $data['status'], $data['content_type'], $data['content_id']);
            Response::success("Content status updated successfully.");
        } else {
            Response::error("Failed to update content status.", 500);
        }
    }

    public function getReports() {
        $db = (new Database())->getConnection();
        $sql = "SELECT r.*, u.enrollment_no as reporter_name, u.email as reporter_email 
                FROM reports r 
                JOIN users u ON r.reporter_id = u.id 
                ORDER BY r.id DESC";
        $stmt = $db->query($sql);
        Response::success("Reports retrieved", $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function updateReportStatus($data, $adminId) {
        $missing = Validator::required(['report_id', 'status'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("UPDATE reports SET status = ? WHERE id = ?");
        
        if ($stmt->execute([$data['status'], $data['report_id']])) {
            $this->logAction($adminId, "Updated report status to: " . $data['status'], "reports", $data['report_id']);
            Response::success("Report status updated successfully.");
        } else {
            Response::error("Failed to update report status.", 500);
        }
    }
}
?>
