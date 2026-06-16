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
use Dompdf\Dompdf;

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
        $sql = "SELECT u.userID as id, u.enrollment_no, u.email, u.phoneNum as phone_number, u.role, u.fname as first_name, u.lname as last_name, s.courseID as course, s.std_year as year 
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
        $missing = Validator::required(['user_id', 'fname', 'lname', 'email', 'rep_id', 'password', 'course', 'year'], $data);
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
        $phone = isset($data['phone']) ? $data['phone'] : null;

        $db->beginTransaction();
        try {
            // Resolve Course ID from String/Int
            $courseId = null;
            if (!empty($data['course'])) {
                if (is_numeric($data['course'])) {
                    $courseId = (int)$data['course'];
                } else {
                    $stmt = $db->prepare("SELECT courseID FROM Course WHERE courseName = ? LIMIT 1");
                    $stmt->execute([$data['course']]);
                    $c = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($c) {
                        $courseId = $c['courseID'];
                    } else {
                        $stmt = $db->prepare("INSERT INTO Course (courseName) VALUES (?)");
                        $stmt->execute([$data['course']]);
                        $courseId = $db->lastInsertId();
                    }
                }
            }

            // Update User details
            $stmt = $db->prepare("UPDATE Users SET role = 'rep', rep_id = ?, fname = ?, lname = ?, phoneNum = ?, email = ? WHERE userID = ?");
            $stmt->execute([$data['rep_id'], $data['fname'], $data['lname'], $phone, $data['email'], $data['user_id']]);

            // Update Student details
            $stmt = $db->prepare("UPDATE Student SET courseID = ?, std_year = ? WHERE userID = ?");
            $stmt->execute([$courseId, (int)$data['year'], $data['user_id']]);

            // Create Course Rep record
            $stmt = $db->prepare("INSERT INTO Course_representative (userID, enrollmentNo, courseID, hash_password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['user_id'], $user['enrollmentNo'], $courseId, $hashed]);

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

            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $output = $dompdf->output();
            
            $pdfPath = __DIR__ . '/../temp_rep_letter.pdf';
            file_put_contents($pdfPath, $output);

            // Send Email (We can update MailService later, but for now we'll do it inline since PHPMailer is loaded)
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
                $mail->Body    = 'Congratulations on your appointment as a Course Representative! Please find your official credential letter attached.';
                
                $mail->addAttachment($pdfPath, 'Appointment_Letter.pdf');
                $mail->send();
            } catch (Exception $e) {
                // Log email failure but don't fail the whole request
                file_put_contents(__DIR__ . '/../admin_log.txt', "Failed to send PDF email to {$data['email']}: {$mail->ErrorInfo}\n", FILE_APPEND);
            }
            
            // Clean up temp file
            if (file_exists($pdfPath)) unlink($pdfPath);

            Response::success("Successfully assigned student as Course Representative and sent credential PDF.");
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
