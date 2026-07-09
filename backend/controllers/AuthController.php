<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Staff.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/MailService.php';
require_once __DIR__ . '/../utils/JWT.php';

class AuthController extends BaseController
{

    private function validateEmailDomain($email, $role)
    {
        $domain = strtolower(substr($email, strpos($email, '@') + 1));

        if ($role === User::ROLE_STUDENT) {
            if ($domain !== 'std.uwu.ac.lk') {
                return "Students must register with a university email ending in @std.uwu.ac.lk. Please use your student email.";
            }
        } else if ($role === User::ROLE_STAFF) {
            if ($domain !== 'gmail.com') {
                return "Staff must register with a Gmail address ending in @gmail.com.";
            }
        }
        return null;
    }

    public function register($data)
    {
        if (isset($data['role']) && $data['role'] === User::ROLE_ADMIN) {
            Response::error("Forbidden: Cannot register as an administrator.", 403);
        }

        $required = ['enrollment_no', 'email', 'password', 'confirm_password', 'role', 'first_name', 'last_name', 'phone_number'];
        if ($data['role'] !== User::ROLE_STUDENT && $data['role'] !== User::ROLE_STAFF) {
            Response::error("Invalid role. Please select Student or Staff.");
        }

        Validator::validateRequired($required, $data);

        if (!Validator::validateName($data['first_name']) || !Validator::validateName($data['last_name'])) {
            Response::error("First name and last name must contain only letters and spaces.");
        }

        if (!Validator::validateEmail($data['email'])) {
            Response::error("Invalid email format. Please enter a valid email address.");
        }

        $emailError = $this->validateEmailDomain($data['email'], $data['role']);
        if ($emailError) {
            Response::error($emailError);
        }

        if ($data['password'] !== $data['confirm_password']) {
            Response::error("Passwords do not match. Please re-enter your password.");
        }

        if (strlen($data['password']) < 6) {
            Response::error("Password must be at least 6 characters long.");
        }

        if (!preg_match('/^[+]?[0-9][\s\-]?([0-9][\s\-]?){6,14}$/', $data['phone_number'])) {
            Response::error("Invalid phone number. Please enter a valid phone number with digits only (7–15 digits).");
        }

        $userModel = new User();

        if ($userModel->findByEnrollment($data['enrollment_no'])) {
            Response::error("This ID is already registered. Try logging in instead.");
        }
        if ($userModel->findByEmail($data['email'])) {
            Response::error("This email address is already registered. Try logging in or use a different email.");
        }

        $userData = [
            'fname' => $data['first_name'],
            'lname' => $data['last_name'],
            'email' => $data['email'],
            'phoneNum' => $data['phone_number'],
            'hash_password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role' => $data['role']
        ];

        $user_id = $userModel->create($userData);

        if ($user_id) {
            if ($data['role'] === User::ROLE_STUDENT) {
                require_once __DIR__ . '/../models/Student.php';
                $studentModel = new Student();

                $courseID = !empty($data['course']) ? $data['course'] : Student::extractCourseFromEnrollment($data['enrollment_no']);

                $studentModel->create([
                    'userID' => $user_id,
                    'enrollmentNo' => $data['enrollment_no'],
                    'courseID' => $courseID,
                    'std_year' => !empty($data['year']) ? $data['year'] : null
                ]);
            } else if ($data['role'] === User::ROLE_STAFF) {
                $staffModel = new Staff();
                $staffModel->create([
                    'staffID' => $data['enrollment_no'],
                    'userID' => $user_id
                ]);
            }

            $otp = rand(100000, 999999);
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("INSERT INTO OTP_verification (userID, otp_code, expired_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
            $stmt->execute([$user_id, $otp]);

            MailService::sendOTP($data['email'], $otp);

            Response::success("Registration successful! An OTP has been sent to your email.", [
                'user_id' => $user_id,
                'email' => $data['email']
            ]);
        } else {
            Response::error("Registration failed. Please try again later.", 500);
        }
    }

    public function login($data)
    {
        Validator::validateRequired(['enrollment_no', 'password'], $data);

        $userModel = new User();
        $user = $userModel->findByEnrollment($data['enrollment_no']);

        $requestedRole = isset($data['role']) ? $data['role'] : User::ROLE_STUDENT;

        if ($user) {

            if (isset($user['is_active']) && $user['is_active'] == 0) {
                Response::error("Your account has been deactivated. Please contact an administrator.");
            }

            $isAuthenticated = false;


            if ($requestedRole === User::ROLE_REP) {
                $db = (new Database())->getConnection();
                $stmt = $db->prepare("SELECT hash_password, is_first_login, is_active FROM Course_representative WHERE userID = ?");
                $stmt->execute([$user['userID']]);
                $repData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($repData) {
                    if (isset($repData['is_active']) && $repData['is_active'] == 0) {
                        Response::error("Your Representative account has been deactivated. Please contact an administrator.");
                    }
                    if (password_verify($data['password'], $repData['hash_password'])) {
                        if (isset($repData['is_first_login']) && $repData['is_first_login'] == 1) {
                            Response::success("First login password change required", [
                                "action" => "force_reset",
                                "user_id" => $user['userID']
                            ]);
                        }
                        $isAuthenticated = true;
                    }
                }
            } else {

                if (password_verify($data['password'], $user['hash_password'])) {
                    // Prevent a staff member from logging in as student etc
                    if ($requestedRole === User::ROLE_STAFF && $user['role'] !== User::ROLE_STAFF) {
                        $isAuthenticated = false;
                    } else if ($requestedRole === User::ROLE_ADMIN && $user['role'] !== User::ROLE_ADMIN) {
                        $isAuthenticated = false;
                    } else {
                        $isAuthenticated = true;
                    }
                }
            }

            if ($isAuthenticated) {
                $userModel->updateLoginTime($user['userID']);
                $db = (new Database())->getConnection();

                if ($user['is_verified']) {
                    $profile = null;
                    if ($user['role'] === User::ROLE_STUDENT) {
                        $stmt = $db->prepare("SELECT enrollmentNo as enrollment_no, courseID, std_year as year FROM Student WHERE userID = ?");
                        $stmt->execute([$user['userID']]);
                        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else if ($user['role'] === User::ROLE_REP) {
                        $stmt = $db->prepare("SELECT s.courseID, s.std_year as year, c.rep_id_string as enrollment_no FROM Student s JOIN Course_representative c ON s.userID = c.userID WHERE s.userID = ?");
                        $stmt->execute([$user['userID']]);
                        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else if ($user['role'] === User::ROLE_STAFF) {
                        $stmt = $db->prepare("SELECT staffID as enrollment_no FROM Staff WHERE userID = ?");
                        $stmt->execute([$user['userID']]);
                        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                    }

                    $finalEnrollment = $profile && !empty($profile['enrollment_no']) ? $profile['enrollment_no'] : ($user['enrollment_no'] ?? null);

                    $userData = [
                        'id' => $user['userID'],
                        'enrollment_no' => $finalEnrollment,
                        'email' => $user['email'],
                        'first_name' => $user['fname'],
                        'last_name' => $user['lname'],
                        'phone_number' => $user['phoneNum'],
                        'lost_item_sms_notification' => isset($user['lost_item_sms_notification']) ? (int)$user['lost_item_sms_notification'] : 0,
                        'peer_learning_app_notification' => isset($user['peer_learning_app_notification']) ? (int)$user['peer_learning_app_notification'] : 1,
                        'has_seen_lost_item_popup' => isset($user['has_seen_lost_item_popup']) ? (int)$user['has_seen_lost_item_popup'] : 0,
                        'role' => $requestedRole // Grant the role they successfully logged in as
                    ];

                    if ($profile) {
                        $userData = array_merge($userData, $profile);
                    }

                    require_once __DIR__ . '/../utils/JWT.php';
                    $token = JWT::generate([
                        'id' => $user['userID'],
                        'role' => $requestedRole
                    ]);
                    $this->setAuthCookie($token);

                    Response::success("Login successful", [
                        'token' => $token,
                        'user' => $userData,
                        'verified' => true,
                        'action' => 'dashboard',
                        'redirect' => $requestedRole === User::ROLE_REP ? 'rep_dashboard' : 'student_dashboard'
                    ]);
                } else {
                    $otp = rand(100000, 999999);
                    $stmt = $db->prepare("INSERT INTO OTP_verification (userID, otp_code, expired_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
                    $stmt->execute([$user['userID'], $otp]);

                    MailService::sendOTP($user['email'], $otp);

                    Response::error("Account not verified. A new OTP has been sent to your email.", 403, ['user_id' => $user['userID'], 'email' => $user['email'], 'needs_verification' => true]);
                }
            } else {
                Response::error("Invalid ID or password.", 401);
            }
        } else {
            Response::error("Invalid ID or password.", 401);
        }
    }

    public function verifyOtp($data)
    {
        Validator::validateRequired(['user_id', 'otp'], $data);

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT * FROM OTP_verification WHERE userID = ? AND otp_code = ? AND expired_at > NOW() ORDER BY otpID DESC LIMIT 1");
        $stmt->execute([$data['user_id'], $data['otp']]);
        $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($otpRecord) {
            $db->prepare("DELETE FROM OTP_verification WHERE userID = ?")->execute([$data['user_id']]);

            $userModel = new User();
            $userModel->markAsVerified($data['user_id']);

            $user = $userModel->findById($data['user_id']);
            $profile = null;
            if ($user['role'] === User::ROLE_STUDENT) {
                $stmt = $db->prepare("SELECT enrollmentNo as enrollment_no, courseID, std_year as year FROM Student WHERE userID = ?");
                $stmt->execute([$data['user_id']]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            } else if ($user['role'] === User::ROLE_REP) {
                $stmt = $db->prepare("SELECT s.courseID, s.std_year as year, c.rep_id_string as enrollment_no FROM Student s JOIN Course_representative c ON s.userID = c.userID WHERE s.userID = ?");
                $stmt->execute([$data['user_id']]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            } else if ($user['role'] === User::ROLE_STAFF) {
                $stmt = $db->prepare("SELECT staffID as enrollment_no FROM Staff WHERE userID = ?");
                $stmt->execute([$data['user_id']]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            $finalEnrollment = $profile && !empty($profile['enrollment_no']) ? $profile['enrollment_no'] : ($user['enrollment_no'] ?? null);

            $userData = [
                'id' => $user['userID'],
                'enrollment_no' => $finalEnrollment,
                'email' => $user['email'],
                'first_name' => $user['fname'],
                'last_name' => $user['lname'],
                'phone_number' => $user['phoneNum'],
                'lost_item_sms_notification' => isset($user['lost_item_sms_notification']) ? (int)$user['lost_item_sms_notification'] : 0,
                'peer_learning_app_notification' => isset($user['peer_learning_app_notification']) ? (int)$user['peer_learning_app_notification'] : 1,
                'has_seen_lost_item_popup' => isset($user['has_seen_lost_item_popup']) ? (int)$user['has_seen_lost_item_popup'] : 0,
                'role' => $user['role']
            ];

            if ($profile) {
                $userData = array_merge($userData, $profile);
            }

            require_once __DIR__ . '/../utils/JWT.php';
            $token = JWT::generate([
                'id' => $user['userID'],
                'role' => $user['role']
            ]);
            $this->setAuthCookie($token);

            Response::success("Email verified successfully! Welcome to UniCore.", [
                'token' => $token,
                'user' => $userData
            ]);
        } else {
            Response::error("Invalid OTP, please enter a valid OTP.", 401);
        }
    }

    public function forgotPassword($data)
    {
        Validator::validateRequired(['email'], $data);

        $userModel = new User();
        $user = $userModel->findByEmail($data['email']);

        if (!$user) {
            Response::error("No account found with this email address. Please check and try again.");
        }

        $otp = rand(100000, 999999);
        $db = (new Database())->getConnection();

        $db->prepare("DELETE FROM OTP_verification WHERE userID = ?")->execute([$user['userID']]);

        $stmt = $db->prepare("INSERT INTO OTP_verification (userID, otp_code, expired_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
        $stmt->execute([$user['userID'], $otp]);

        MailService::sendOTP($user['email'], $otp);

        Response::success("OTP sent to your email. Please check your inbox.", [
            'user_id' => $user['userID'],
            'email' => $user['email']
        ]);
    }

    public function resendOtp($data)
    {
        Validator::validateRequired(['user_id'], $data);

        $userModel = new User();
        $user = $userModel->findById($data['user_id']);

        if (!$user) {
            Response::error("User not found.", 404);
        }

        if ($user['is_verified']) {
            Response::error("This account is already verified. Please log in.");
        }

        $db = (new Database())->getConnection();
        $db->prepare("DELETE FROM OTP_verification WHERE userID = ?")->execute([$data['user_id']]);

        $otp = rand(100000, 999999);

        $stmt = $db->prepare("INSERT INTO OTP_verification (userID, otp_code, expired_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
        $stmt->execute([$data['user_id'], $otp]);

        MailService::sendOTP($user['email'], $otp);

        Response::success("A new OTP has been sent to your email. It is valid for 2 minutes.", [
            'user_id' => $data['user_id'],
            'email'   => $user['email']
        ]);
    }

    public function verifyResetOtp($data)
    {
        Validator::validateRequired(['user_id', 'otp'], $data);

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT * FROM OTP_verification WHERE userID = ? AND otp_code = ? AND expired_at > NOW() ORDER BY otpID DESC LIMIT 1");
        $stmt->execute([$data['user_id'], $data['otp']]);
        $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($otpRecord) {
            $db->prepare("DELETE FROM OTP_verification WHERE userID = ?")->execute([$data['user_id']]);

            $resetToken = bin2hex(random_bytes(32));
            $stmt = $db->prepare("INSERT INTO OTP_verification (userID, otp_code, expired_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
            $stmt->execute([$data['user_id'], $resetToken]);

            Response::success("OTP verified successfully. You can now reset your password.", [
                'reset_token' => $resetToken,
                'user_id' => $data['user_id']
            ]);
        } else {
            Response::error("Invalid OTP, please enter a valid OTP.", 401);
        }
    }

    public function resetPassword($data)
    {
        Validator::validateRequired(['user_id', 'reset_token', 'new_password', 'confirm_password'], $data);

        if ($data['new_password'] !== $data['confirm_password']) {
            Response::error("Passwords do not match.");
        }

        $db = (new Database())->getConnection();

        $stmt = $db->prepare("SELECT * FROM OTP_verification WHERE userID = ? AND otp_code = ? AND expired_at > NOW() ORDER BY otpID DESC LIMIT 1");
        $stmt->execute([$data['user_id'], $data['reset_token']]);
        $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenRecord) {
            Response::error("Invalid or expired reset session. Please start the process again.", 401);
        }

        $db->prepare("DELETE FROM OTP_verification WHERE userID = ?")->execute([$data['user_id']]);

        $userModel = new User();
        $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        $userModel->updatePassword($data['user_id'], $newHash);

        Response::success("Password has been reset successfully! You can now log in with your new password.");
    }

    public function updateProfile($data, $user_id, $logged_in_role = null)
    {
        $required = ['first_name', 'last_name', 'email', 'phone_number'];
        Validator::validateRequired($required, $data);

        if (!Validator::validateName($data['first_name']) || !Validator::validateName($data['last_name'])) {
            Response::error("First name and last name must contain only letters and spaces.");
        }

        if (!preg_match('/^[0-9]+$/', $data['phone_number'])) {
            Response::error("Phone number must contain only numbers.");
        }

        $db = (new Database())->getConnection();
        $userModel = new User();
        $user = $userModel->findById($user_id);
        if (!$user) {
            Response::error("User not found.", 404);
        }

        $emailError = $this->validateEmailDomain($data['email'], $user['role']);
        if ($emailError) {
            Response::error($emailError);
        }

        $stmt = $db->prepare("SELECT userID FROM Users WHERE email = ? AND userID != ? LIMIT 1");
        $stmt->execute([$data['email'], $user_id]);
        if ($stmt->fetch()) {
            Response::error("This email is already in use by another account.");
        }

        $password_hash = $user['hash_password'];
        $is_rep_password = ($logged_in_role === User::ROLE_REP);

        if ($is_rep_password) {
            $stmt = $db->prepare("SELECT hash_password FROM Course_representative WHERE userID = ?");
            $stmt->execute([$user_id]);
            $repData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($repData) {
                $password_hash = $repData['hash_password'];
            }
        }

        if (!empty($data['new_password'])) {
            if (!password_verify($data['old_password'], $password_hash)) {
                Response::error("Incorrect current password.");
            }
            if ($data['new_password'] !== $data['confirm_password']) {
                Response::error("New passwords do not match.");
            }
            $password_hash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        }

        $db->beginTransaction();
        try {
            $phone = isset($data['phone_number']) ? $data['phone_number'] : null;
            $smsPref = isset($data['lost_item_sms_notification']) ? (int)$data['lost_item_sms_notification'] : 0;
            $peerPref = isset($data['peer_learning_app_notification']) ? (int)$data['peer_learning_app_notification'] : 1;

            if ($is_rep_password && !empty($data['new_password'])) {
                // Update general profile in Users table without modifying hash_password
                $stmt = $db->prepare("UPDATE Users SET fname = ?, lname = ?, email = ?, phoneNum = ?, lost_item_sms_notification = ?, peer_learning_app_notification = ? WHERE userID = ?");
                $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $phone, $smsPref, $peerPref, $user_id]);

                // Update password in Course_representative table
                $stmt = $db->prepare("UPDATE Course_representative SET hash_password = ? WHERE userID = ?");
                $stmt->execute([$password_hash, $user_id]);
            } else {
                $stmt = $db->prepare("UPDATE Users SET fname = ?, lname = ?, email = ?, phoneNum = ?, hash_password = ?, lost_item_sms_notification = ?, peer_learning_app_notification = ? WHERE userID = ?");
                $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $phone, $password_hash, $smsPref, $peerPref, $user_id]);
            }

            if ($user['role'] === User::ROLE_STUDENT || $user['role'] === User::ROLE_REP) {
                $course = (isset($data['course']) && $data['course'] !== '' && is_numeric($data['course'])) ? (int)$data['course'] : null;
                $year = (isset($data['year']) && $data['year'] !== '' && is_numeric($data['year'])) ? (int)$data['year'] : null;
                $stmt = $db->prepare("UPDATE Student SET courseID = ?, std_year = ? WHERE userID = ?");
                $stmt->execute([$course, $year, $user_id]);
            } else if ($user['role'] === User::ROLE_STAFF) {
                // Department logic removed
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            Response::error("Failed to update profile: " . $e->getMessage(), 500);
        }

        $updatedUser = $userModel->findById($user_id);
        $profile = null;
        if ($updatedUser['role'] === User::ROLE_STUDENT) {
            $stmt = $db->prepare("SELECT enrollmentNo as enrollment_no, courseID, std_year as year FROM Student WHERE userID = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        } else if ($updatedUser['role'] === User::ROLE_REP) {
            $stmt = $db->prepare("SELECT s.courseID, s.std_year as year, c.rep_id_string as enrollment_no FROM Student s JOIN Course_representative c ON s.userID = c.userID WHERE s.userID = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        } else if ($updatedUser['role'] === User::ROLE_STAFF) {
            $stmt = $db->prepare("SELECT staffID as enrollment_no FROM Staff WHERE userID = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $finalRole = $logged_in_role ? $logged_in_role : $updatedUser['role'];
        $finalEnrollment = $profile && !empty($profile['enrollment_no']) ? $profile['enrollment_no'] : ($updatedUser['enrollment_no'] ?? null);

        $userData = [
            'id' => $updatedUser['userID'],
            'enrollment_no' => $finalEnrollment,
            'email' => $updatedUser['email'],
            'first_name' => $updatedUser['fname'],
            'last_name' => $updatedUser['lname'],
            'role' => $finalRole,
            'phone_number' => $updatedUser['phoneNum'],
            'lost_item_sms_notification' => isset($updatedUser['lost_item_sms_notification']) ? (int)$updatedUser['lost_item_sms_notification'] : 0,
            'peer_learning_app_notification' => isset($updatedUser['peer_learning_app_notification']) ? (int)$updatedUser['peer_learning_app_notification'] : 1,
            'has_seen_lost_item_popup' => isset($updatedUser['has_seen_lost_item_popup']) ? (int)$updatedUser['has_seen_lost_item_popup'] : 0
        ];

        if ($profile) {
            $userData = array_merge($userData, $profile);
        }

        require_once __DIR__ . '/../utils/JWT.php';
        $token = JWT::generate([
            'id' => $updatedUser['userID'],
            'role' => $finalRole
        ]);
        $this->setAuthCookie($token);

        Response::success("Profile updated successfully", [
            'token' => $token,
            'user' => $userData
        ]);
    }

    public function forceChangeRepPassword($data)
    {
        Validator::validateRequired(['user_id', 'new_password'], $data);

        if (strlen($data['new_password']) < 6) {
            Response::error("Password must be at least 6 characters long.");
        }

        $db = (new Database())->getConnection();
        $hashed = password_hash($data['new_password'], PASSWORD_BCRYPT);

        $stmt = $db->prepare("UPDATE Course_representative SET hash_password = ?, is_first_login = 0 WHERE userID = ?");
        if ($stmt->execute([$hashed, $data['user_id']])) {
            Response::success("Password changed successfully! Please login with your new password.");
        } else {
            Response::error("Failed to update password.", 500);
        }
    }

    private function setAuthCookie($token)
    {
        setcookie(
            'auth_token',
            $token,
            [
                'expires' => time() + 86400 * 30, // 30 days
                'path' => '/',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    public function logout()
    {
        setcookie('auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        Response::success("Logged out successfully");
    }

    public function me()
    {
        error_log("me() endpoint hit by " . $_SERVER['REMOTE_ADDR']);
        require_once __DIR__ . '/../utils/AuthMiddleware.php';
        try {
            $decoded = AuthMiddleware::authenticate(true);
            if (!$decoded) {
                Response::success("Not authenticated", ['user' => null]);
            }
        } catch (Exception $e) {
            error_log("me() auth failed: " . $e->getMessage());
            Response::success("Not authenticated", ['user' => null]);
        }
        $userID = $decoded['id'];
        $role = $decoded['role'];
        error_log("me() authenticated user $userID as $role");

        $db = (new Database())->getConnection();
        require_once __DIR__ . '/../models/User.php';
        $userModel = new User();
        $user = $userModel->findById($userID);

        if (!$user) {
            Response::error("User not found", 404);
        }

        $userData = [
            'id' => $user['userID'],
            'email' => $user['email'],
            'first_name' => $user['fname'],
            'last_name' => $user['lname'],
            'phone_number' => $user['phoneNum'],
            'lost_item_sms_notification' => isset($user['lost_item_sms_notification']) ? (int)$user['lost_item_sms_notification'] : 0,
            'peer_learning_app_notification' => isset($user['peer_learning_app_notification']) ? (int)$user['peer_learning_app_notification'] : 1,
            'has_seen_lost_item_popup' => isset($user['has_seen_lost_item_popup']) ? (int)$user['has_seen_lost_item_popup'] : 0,
            'role' => $role
        ];

        if ($role === User::ROLE_STUDENT || $role === User::ROLE_REP) {
            require_once __DIR__ . '/../models/Student.php';
            $studentModel = new Student();
            $profile = $studentModel->getProfile($userID);
            if ($profile) {
                $userData['enrollment_no'] = $profile['enrollmentNo'];
                $userData['course_id'] = $profile['courseID'];
                $userData['std_year'] = $profile['std_year'];
            }
            if ($role === User::ROLE_REP) {
                $stmt = $db->prepare("SELECT rep_id_string FROM Course_representative WHERE userID = ?");
                $stmt->execute([$userID]);
                $repProfile = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($repProfile && !empty($repProfile['rep_id_string'])) {
                    $userData['enrollment_no'] = $repProfile['rep_id_string'];
                }
            }
        } elseif ($role === User::ROLE_STAFF) {
            $stmt = $db->prepare("SELECT staffID as enrollment_no FROM Staff WHERE userID = ?");
            $stmt->execute([$userID]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($profile) {
                $userData['enrollment_no'] = $profile['enrollment_no'];
            }
        } elseif ($role === User::ROLE_ADMIN) {
            $stmt = $db->prepare("SELECT adminID FROM Admin WHERE userID = ?");
            $stmt->execute([$userID]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($profile) {
                $userData['admin_id'] = $profile['adminID'];
            }
        }

        Response::success("User session retrieved", [
            'user' => $userData
        ]);
    }
}
