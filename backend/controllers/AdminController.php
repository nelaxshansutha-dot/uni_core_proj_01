<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Staff.php';
require_once __DIR__ . '/../models/CourseRep.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/MailService.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

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
                       s.enrollmentNo as enrollment_no, 
                       st.staffID as staff_id,
                       u.email, u.phoneNum as phone_number, u.role, u.is_verified, 
                       u.is_active, u.created_at, 
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
            $sql .= " AND (s.enrollmentNo LIKE :q OR u.email LIKE :q OR u.fname LIKE :q OR u.lname LIKE :q)";
            $params[':q'] = "%" . $query . "%";
        }

        // Fetch main user accounts
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch secondary rep accounts
        $repSql = "SELECT CONCAT('rep_', cr.userID) as id, 
                          s.enrollmentNo as enrollment_no, 
                          NULL as staff_id,
                          u.email, u.phoneNum as phone_number, 'rep' as role, u.is_verified, 
                          cr.is_active, u.created_at, 
                          u.fname as first_name, u.lname as last_name,
                          s.courseID as course, s.std_year as year,
                          NULL as department
                   FROM Course_representative cr
                   JOIN Users u ON cr.userID = u.userID
                   LEFT JOIN Student s ON cr.userID = s.userID
                   WHERE 1=1";
                   
        $repParams = [];
        if (!empty($role) && $role !== 'rep') {
            // If filtering for non-rep roles, don't fetch reps
            $repSql .= " AND 1=0"; 
        }
        if (!empty($query)) {
            $repSql .= " AND (s.enrollmentNo LIKE :q OR u.email LIKE :q OR u.fname LIKE :q OR u.lname LIKE :q)";
            $repParams[':q'] = "%" . $query . "%";
        }
        $repStmt = $db->prepare($repSql);
        $repStmt->execute($repParams);
        $reps = $repStmt->fetchAll(PDO::FETCH_ASSOC);

        // Merge arrays
        $allUsers = array_merge($users, $reps);

        // Sort by created_at descending (approximate sorting)
        usort($allUsers, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Cast tinyint to boolean for frontend so '0' doesn't evaluate as truthy
        foreach ($allUsers as &$u) {
            $u['is_active'] = (bool)$u['is_active'];
            $u['is_verified'] = (bool)$u['is_verified'];
        }

        Response::success("Users retrieved", $allUsers);
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
            $db = (new Database())->getConnection();
            $db->prepare("UPDATE Users SET is_verified = 1 WHERE userID = ?")->execute([$user_id]);

            if ($data['role'] === 'student' || $data['role'] === 'rep') {
                $studentModel = new Student();
                $studentModel->create([
                    'userID' => $user_id,
                    'enrollmentNo' => $data['enrollment_no'],
                    'courseID' => !empty($data['course']) ? $data['course'] : null,
                    'std_year' => !empty($data['year']) ? $data['year'] : null
                ]);
            } else if ($data['role'] === 'staff') {
                $staffModel = new Staff();
                $staffModel->create([
                    'userID' => $user_id,
                    'staffID' => $data['enrollment_no'] ?? null,
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

        // Handle rep_ prefix if they edit the rep row
        $isRepRow = strpos((string)$id, 'rep_') === 0;
        $realId = $isRepRow ? (int)str_replace('rep_', '', $id) : (int)$id;

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT role FROM Users WHERE userID = ?");
        $stmt->execute([$realId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            Response::error("User not found", 404);
        }

        $db->beginTransaction();
        try {
            $phone = isset($data['phone_number']) ? $data['phone_number'] : null;
            $stmt = $db->prepare("UPDATE Users SET email = ?, phoneNum = ?, fname = ?, lname = ? WHERE userID = ?");
            $stmt->execute([$data['email'], $phone, $data['first_name'], $data['last_name'], $realId]);

            if ($user['role'] === 'staff') {
                $dept = isset($data['department']) ? $data['department'] : '';
                $stmt = $db->prepare("UPDATE Staff SET dept = ? WHERE userID = ?");
                $stmt->execute([$dept, $realId]);
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
            Response::error("Missing is_active flag");
        }
        $db = (new Database())->getConnection();
        $isActive = $data['is_active'] ? 1 : 0;
        
        if (strpos((string)$id, 'rep_') === 0) {
            $realId = (int)str_replace('rep_', '', $id);
            $stmt = $db->prepare("UPDATE Course_representative SET is_active = ? WHERE userID = ?");
            $stmt->execute([$isActive, $realId]);
        } else {
            $realId = (int)$id;
            $stmt = $db->prepare("UPDATE Users SET is_active = ? WHERE userID = ?");
            $stmt->execute([$isActive, $realId]);
        }
        
        Response::success("User status updated successfully.");
    }

    public function searchStudents($query) {
        $db = (new Database())->getConnection();
        $q = "%" . $query . "%";
        $sql = "SELECT u.userID as id, s.enrollmentNo as enrollment_no, u.email, u.phoneNum as phone_number, u.role, u.fname as first_name, u.lname as last_name, s.courseID as course, s.std_year as year 
                FROM Users u 
                JOIN Student s ON u.userID = s.userID 
                WHERE (s.enrollmentNo LIKE :q OR u.fname LIKE :q OR u.lname LIKE :q) 
                AND u.role IN ('student', 'rep')";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':q', $q);
        $stmt->execute();
        
        Response::success("Students found", $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function assignRep($data, $adminId) {
        $missing = Validator::required(['user_id', 'fname', 'lname', 'email', 'rep_id', 'password'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        $db = (new Database())->getConnection();
        
        $stmt = $db->prepare("SELECT u.*, s.enrollmentNo, s.courseID FROM Users u JOIN Student s ON u.userID = s.userID WHERE u.userID = ?");
        $stmt->execute([$data['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Response::error("Student not found.");
        }

        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);
        $phone = isset($data['phone']) ? $data['phone'] : null;
        $courseId = $user['courseID']; // Use existing student course ID

        $db->beginTransaction();
        try {
            // Update User details (keep existing role, usually student)
            $stmt = $db->prepare("UPDATE Users SET fname = ?, lname = ?, phoneNum = ?, email = ? WHERE userID = ?");
            $stmt->execute([$data['fname'], $data['lname'], $phone, $data['email'], $data['user_id']]);

            // Create Course Rep record or Update if exists
            $stmt = $db->prepare("INSERT INTO Course_representative (userID, enrollmentNo, courseID, hash_password, is_first_login, rep_id_string) 
                                  VALUES (?, ?, ?, ?, 1, ?)
                                  ON DUPLICATE KEY UPDATE 
                                  courseID = VALUES(courseID), 
                                  hash_password = VALUES(hash_password), 
                                  is_first_login = 1,
                                  rep_id_string = VALUES(rep_id_string)");
            $stmt->execute([$data['user_id'], $user['enrollmentNo'], $courseId, $hashed, $data['rep_id']]);

            $db->commit();

            // Generate PDF
            $html = "
                <div style='font-family: Arial, sans-serif; padding: 20px;'>
                    <h1 style='color: #6a0dad; border-bottom: 2px solid #6a0dad; padding-bottom: 10px;'>UniCore Course Representative Appointment</h1>
                    <p>Dear {$data['fname']} {$data['lname']},</p>
                    <p>Congratulations! You have been officially appointed as a Course Representative.</p>
                    <p>Below are your exclusive Rep Dashboard login credentials:</p>
                    <div style='background: #f4f6f9; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p><strong>Rep ID:</strong> {$data['rep_id']}</p>
                        <p><strong>Temporary Password:</strong> {$data['password']}</p>
                    </div>
                    <p><em>Note: Your standard student login (Enrollment Number) remains active for accessing the Student Dashboard.</em></p>
                    <br/>
                    <p>Best Regards,</p>
                    <p><strong>The UniCore Admin Team</strong></p>
                </div>
            ";

            // Send Email directly as HTML
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nelanelaxshan@gmail.com';
                $mail->Password = 'yqyflurcewldkwix';
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('nelanelaxshan@gmail.com', 'UniCore Admin');
                $mail->addAddress($data['email'], $data['fname']);

                $mail->isHTML(true);
                $mail->Subject = 'Official Course Representative Appointment';
                $mail->Body    = $html;
                
                $mail->send();
            } catch (Exception $e) {
                // Log email failure but don't fail the whole request
                file_put_contents(__DIR__ . '/../admin_log.txt', "Failed to send email to {$data['email']}: {$mail->ErrorInfo}\n", FILE_APPEND);
            }

            Response::success("Successfully assigned student as Course Representative and sent credential email.");
        } catch (Throwable $e) {
            $db->rollBack();
            file_put_contents(__DIR__ . '/../admin_log.txt', "AssignRep Exception: " . $e->getMessage() . "\n", FILE_APPEND);
            Response::error("Failed to assign Rep: " . $e->getMessage(), 500);
        }
    }

    public function getContent($type = '') {
        $db = (new Database())->getConnection();

        $lostItems = [];
        $marketplace = [];
        $notes = [];

        if (empty($type) || $type === 'lost_item') {
            $stmt = $db->query("SELECT l.lostID as lost_id, l.lostItemName, l.last_seen_date, l.last_seen_time, l.item_image, l.contact_number as contact_no, l.created_at, l.status, l.is_flagged, u.email, s.enrollmentNo as enrollment_no
                                FROM Lost_items l 
                                JOIN Users u ON l.userID = u.userID 
                                LEFT JOIN Student s ON u.userID = s.userID
                                ORDER BY l.lostID DESC");
            $lostItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($type) || $type === 'marketplace') {
            $stmt = $db->query("SELECT m.productID as id, m.productName as title, m.price, m.location, m.image_url as product_image, m.phone_number as contact_no, m.created_at, m.status, m.is_flagged, u.email, s.enrollmentNo as enrollment_no
                                FROM marketplace m 
                                JOIN Users u ON m.userID = u.userID 
                                LEFT JOIN Student s ON u.userID = s.userID
                                ORDER BY m.productID DESC");
            $marketplace = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($type) || $type === 'notes') {
            $stmt = $db->query("SELECT n.noteID as id, n.title, n.courseUnitID, n.file_url, n.created_at, n.status, n.is_flagged, u.email, s.enrollmentNo as enrollment_no
                                FROM Notes n 
                                JOIN Student s ON n.enrollmentNo = s.enrollmentNo
                                JOIN Users u ON s.userID = u.userID 
                                ORDER BY n.noteID DESC");
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        foreach ($lostItems as &$item) {
            $item['is_flagged'] = (bool)$item['is_flagged'];
        }
        foreach ($marketplace as &$item) {
            $item['is_flagged'] = (bool)$item['is_flagged'];
        }
        foreach ($notes as &$item) {
            $item['is_flagged'] = (bool)$item['is_flagged'];
        }

        Response::success("Content retrieved", [
            'lost_items' => $lostItems,
            'marketplace' => $marketplace,
            'notes' => $notes
        ]);
    }

    public function updateContentStatus($data, $adminId) {
        if (!isset($data['content_type']) || !isset($data['content_id']) || !isset($data['status'])) {
            Response::error("Missing required fields.", 400);
        }

        $db = (new Database())->getConnection();
        $type = $data['content_type'];
        $id = (int)$data['content_id'];
        $status = $data['status'];

        try {
            if ($type === 'lost_item') {
                $stmt = $db->prepare("UPDATE Lost_items SET status = ?, is_flagged = 0 WHERE lostID = ?");
                $stmt->execute([$status, $id]);
            } else if ($type === 'marketplace') {
                $stmt = $db->prepare("UPDATE marketplace SET status = ?, is_flagged = 0 WHERE productID = ?");
                $stmt->execute([$status, $id]);
            } else if ($type === 'notes') {
                $stmt = $db->prepare("UPDATE Notes SET status = ?, is_flagged = 0 WHERE noteID = ?");
                $stmt->execute([$status, $id]);
            } else {
                Response::error("Invalid content type.");
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
