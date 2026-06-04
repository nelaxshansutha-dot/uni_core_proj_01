<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Staff.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/MailService.php';


class AuthController {
    
    /**
     * Validate email domain based on role.
     * Students: must use @std.uwu.ac.lk OR any non-uwu email
     * Staff: must use @uwu.ac.lk OR any non-uwu email
     * Cannot cross: student can't use @uwu.ac.lk, staff can't use @std.uwu.ac.lk
     */
    private function validateEmailDomain($email, $role) {
        $domain = strtolower(substr($email, strpos($email, '@') + 1));
        
        if ($role === 'student') {
            // Students can use @std.uwu.ac.lk or any non-uwu domain
            if ($domain === 'uwu.ac.lk') {
                return "Students cannot register with @uwu.ac.lk. Use @std.uwu.ac.lk or your personal email.";
            }
        } else if ($role === 'staff') {
            // Staff can use @uwu.ac.lk or any non-uwu domain
            if ($domain === 'std.uwu.ac.lk') {
                return "Staff cannot register with @std.uwu.ac.lk. Use @uwu.ac.lk or your personal email.";
            }
        }
        
        return null; // valid
    }

    public function register($data) {
        $required = ['enrollment_no', 'email', 'password', 'confirm_password', 'role', 'first_name', 'last_name', 'phone_number'];
        if ($data['role'] === 'staff') {
            $required = array_merge($required, ['department']);
        } else if ($data['role'] !== 'student') {
            Response::error("Invalid role. Please select Student or Staff.");
        }

        $missing = Validator::required($required, $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        if (!Validator::validateEmail($data['email'])) {
            Response::error("Invalid email format. Please enter a valid email address.");
        }

        // Validate email domain
        $emailError = $this->validateEmailDomain($data['email'], $data['role']);
        if ($emailError) {
            Response::error($emailError);
        }

        // Validate password match
        if ($data['password'] !== $data['confirm_password']) {
            Response::error("Passwords do not match. Please re-enter your password.");
        }

        // Validate password strength
        if (strlen($data['password']) < 6) {
            Response::error("Password must be at least 6 characters long.");
        }

        // Validate phone number
        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $data['phone_number'])) {
            Response::error("Invalid phone number format.");
        }

        $userModel = new User();
        
        if ($userModel->findByEnrollment($data['enrollment_no'])) {
            Response::error("This enrollment number is already registered. Try logging in instead.");
        }
        if ($userModel->findByEmail($data['email'])) {
            Response::error("This email address is already registered. Try logging in or use a different email.");
        }

        $userData = [
            'enrollment_no' => $data['enrollment_no'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role' => $data['role']
        ];

        $user_id = $userModel->create($userData);

        if ($user_id) {
            if ($data['role'] === 'student') {
                $studentModel = new Student();
                $studentModel->create([
                    'user_id' => $user_id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'course' => null,
                    'year' => null
                ]);
            } else if ($data['role'] === 'staff') {
                $staffModel = new Staff();
                $staffModel->create([
                    'user_id' => $user_id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'department' => $data['department']
                ]);
            }

            // Generate OTP for first-time verification
            $otp = rand(100000, 999999);
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("INSERT INTO otp_verifications (user_id, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
            $stmt->execute([$user_id, $otp]);

            // Send OTP to email
            MailService::sendOTP($data['email'], $otp);


            Response::success("Registration successful! An OTP has been sent to your email.", [
                'user_id' => $user_id,
                'email' => $data['email']
            ]);
        } else {
            Response::error("Registration failed. Please try again later.", 500);
        }
    }

    public function login($data) {
        $missing = Validator::required(['enrollment_no', 'password'], $data);
        if (!empty($missing)) {
            Response::error("Please enter your Enrollment Number and Password.");
        }

        $userModel = new User();
        $user = $userModel->findByEnrollment($data['enrollment_no']);

        if ($user && password_verify($data['password'], $user['password_hash'])) {

            // Normal login (enrollment number + password) does NOT trigger OTP and directly allows access
            $db = (new Database())->getConnection();
            
            // Check if user is already verified
            if ($user['is_verified']) {
                // Already verified — skip OTP, login directly
                $db = (new Database())->getConnection();

                // Fetch profile data based on role
                $profile = null;
                if ($user['role'] === 'student' || $user['role'] === 'rep') {
                    $stmt = $db->prepare("SELECT first_name, last_name, course, year FROM students WHERE user_id = ?");
                    $stmt->execute([$user['id']]);
                    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                } else if ($user['role'] === 'staff') {
                    $stmt = $db->prepare("SELECT first_name, last_name, department FROM staff WHERE user_id = ?");
                    $stmt->execute([$user['id']]);
                    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                }

                $userData = [
                    'id' => $user['id'],
                    'enrollment_no' => $user['enrollment_no'],
                    'email' => $user['email'],
                    'phone_number' => $user['phone_number'],
                    'lost_item_sms_notification' => $user['lost_item_sms_notification'],
                    'has_seen_lost_item_popup' => $user['has_seen_lost_item_popup'],
                    'role' => $user['role']
                ];

                if ($profile) {
                    $userData = array_merge($userData, $profile);
                }

                $token = base64_encode(json_encode(['id' => $user['id'], 'role' => $user['role'], 'time' => time()]));

                Response::success("Login successful", [
                    'token' => $token,
                    'user' => $userData,
                    'verified' => true
                ]);
            } else {
                // Not verified — generate OTP for first-time verification
                $otp = rand(100000, 999999);
                $db = (new Database())->getConnection();


            // Fetch profile data based on role
            $profile = null;
            if ($user['role'] === 'student' || $user['role'] === 'rep') {
                $stmt = $db->prepare("SELECT first_name, last_name, course, year FROM students WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            } else if ($user['role'] === 'staff') {
                $stmt = $db->prepare("SELECT first_name, last_name, department FROM staff WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            $userData = [
                'id' => $user['id'],
                'enrollment_no' => $user['enrollment_no'],
                'email' => $user['email'],
                'role' => $user['role']
            ];

            if ($profile) {
                $userData = array_merge($userData, $profile);
            }

            require_once __DIR__ . '/../utils/JWT.php';
            $token = JWT::generate([
                'id' => $user['id'],
                'role' => $user['role']
            ]);

            Response::success("Login successful", [
                'token' => $token,
                'user' => $userData,
                'verified' => true
            ]);
        } else {
            Response::error("Invalid enrollment number or password. Please check your credentials.", 401);
        }
    }

    public function verifyOtp($data) {
        $missing = Validator::required(['user_id', 'otp'], $data);
        if (!empty($missing)) {
            Response::error("Please enter the OTP code.");
        }

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT * FROM otp_verifications WHERE user_id = ? AND otp_code = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$data['user_id'], $data['otp']]);
        $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($otpRecord) {
            // OTP is valid — clean up and mark user as verified
            $db->prepare("DELETE FROM otp_verifications WHERE user_id = ?")->execute([$data['user_id']]);
            
            // Mark user as verified permanently
            $userModel = new User();
            $userModel->markAsVerified($data['user_id']);

            // Get user details
            $stmt = $db->prepare("SELECT id, enrollment_no, email, phone_number, lost_item_sms_notification, has_seen_lost_item_popup, role FROM users WHERE id = ?");
            $stmt->execute([$data['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Fetch profile data based on role
            $profile = null;
            if ($user['role'] === 'student' || $user['role'] === 'rep') {
                $stmt = $db->prepare("SELECT first_name, last_name, course, year FROM students WHERE user_id = ?");
                $stmt->execute([$data['user_id']]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            } else if ($user['role'] === 'staff') {
                $stmt = $db->prepare("SELECT first_name, last_name, department FROM staff WHERE user_id = ?");
                $stmt->execute([$data['user_id']]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($profile) {
                $user = array_merge($user, $profile);
            }

            require_once __DIR__ . '/../utils/JWT.php';
            $token = JWT::generate([
                'id' => $user['id'],
                'role' => $user['role']
            ]);

            Response::success("Email verified successfully! Welcome to UniCore.", [
                'token' => $token,
                'user' => $user
            ]);
        } else {
            Response::error("Invalid or expired OTP. Please request a new one.", 401);
        }
    }

    /**
     * Forgot Password — Send OTP to the user's registered email
     */
    public function forgotPassword($data) {
        $missing = Validator::required(['email'], $data);
        if (!empty($missing)) {
            Response::error("Please enter your registered email address.");
        }

        if (!Validator::validateEmail($data['email'])) {
            Response::error("Invalid email format.");
        }

        $userModel = new User();
        $user = $userModel->findByEmail($data['email']);

        if (!$user) {
            Response::error("No account found with this email address. Please check and try again.");
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $db = (new Database())->getConnection();

        // Delete old OTPs for user
        $db->prepare("DELETE FROM otp_verifications WHERE user_id = ?")->execute([$user['id']]);

        // Insert new OTP (10 min expiry)
        $stmt = $db->prepare("INSERT INTO otp_verifications (user_id, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
        $stmt->execute([$user['id'], $otp]);

        // Send OTP to email
        MailService::sendOTP($user['email'], $otp);


        Response::success("OTP sent to your email. Please check your inbox (or otp_log.txt for testing).", [
            'user_id' => $user['id'],
            'email' => $user['email']
        ]);
    }

    /**
     * Verify Forgot Password OTP — returns a temporary reset token
     */
    public function verifyResetOtp($data) {
        $missing = Validator::required(['user_id', 'otp'], $data);
        if (!empty($missing)) {
            Response::error("Please enter the OTP code.");
        }

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT * FROM otp_verifications WHERE user_id = ? AND otp_code = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$data['user_id'], $data['otp']]);
        $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($otpRecord) {
            // OTP valid — delete it and generate a reset token
            $db->prepare("DELETE FROM otp_verifications WHERE user_id = ?")->execute([$data['user_id']]);

            // Generate a temporary reset token (valid for 15 minutes)
            $resetToken = bin2hex(random_bytes(32));
            $stmt = $db->prepare("INSERT INTO otp_verifications (user_id, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
            $stmt->execute([$data['user_id'], $resetToken]);

            Response::success("OTP verified successfully. You can now reset your password.", [
                'reset_token' => $resetToken,
                'user_id' => $data['user_id']
            ]);
        } else {
            Response::error("Invalid or expired OTP. Please try again.", 401);
        }
    }

    /**
     * Reset Password — use the reset token to change the password
     */
    public function resetPassword($data) {
        $missing = Validator::required(['user_id', 'reset_token', 'new_password', 'confirm_password'], $data);
        if (!empty($missing)) {
            Response::error("Please fill in all fields.");
        }

        if ($data['new_password'] !== $data['confirm_password']) {
            Response::error("Passwords do not match.");
        }

        if (strlen($data['new_password']) < 6) {
            Response::error("Password must be at least 6 characters long.");
        }

        $db = (new Database())->getConnection();

        // Verify the reset token
        $stmt = $db->prepare("SELECT * FROM otp_verifications WHERE user_id = ? AND otp_code = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$data['user_id'], $data['reset_token']]);
        $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenRecord) {
            Response::error("Invalid or expired reset session. Please start the process again.", 401);
        }

        // Delete the reset token
        $db->prepare("DELETE FROM otp_verifications WHERE user_id = ?")->execute([$data['user_id']]);

        // Update the password
        $userModel = new User();
        $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        $userModel->updatePassword($data['user_id'], $newHash);

        Response::success("Password has been reset successfully! You can now log in with your new password.");
    }

    /**
     * Update User Profile Details
     */
    public function updateProfile($data, $user_id) {
        $required = ['first_name', 'last_name', 'email'];
        $missing = Validator::required($required, $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        if (!Validator::validateEmail($data['email'])) {
            Response::error("Invalid email format.");
        }

        $db = (new Database())->getConnection();

        // Fetch current user details
        $userModel = new User();
        $user = $userModel->findById($user_id);
        if (!$user) {
            Response::error("User not found.", 404);
        }

        // Validate email domain based on role
        $emailError = $this->validateEmailDomain($data['email'], $user['role']);
        if ($emailError) {
            Response::error($emailError);
        }

        // Check if email is already taken by another user
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$data['email'], $user_id]);
        if ($stmt->fetch()) {
            Response::error("This email is already in use by another account.");
        }

        // Handle password update if requested
        $password_hash = $user['password_hash'];
        if (!empty($data['new_password'])) {
            if (empty($data['old_password'])) {
                Response::error("Please enter your current password to change your password.");
            }
            if (!password_verify($data['old_password'], $user['password_hash'])) {
                Response::error("Incorrect current password.");
            }
            if (strlen($data['new_password']) < 6) {
                Response::error("New password must be at least 6 characters long.");
            }
            if ($data['new_password'] !== $data['confirm_password']) {
                Response::error("New passwords do not match.");
            }
            $password_hash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        }

        // Start Transaction
        $db->beginTransaction();

        try {
            // Update users table
            $phone = isset($data['phone_number']) ? $data['phone_number'] : null;
            $stmt = $db->prepare("UPDATE users SET email = ?, phone_number = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$data['email'], $phone, $password_hash, $user_id]);

            // Update role-specific table
            if ($user['role'] === 'student' || $user['role'] === 'rep') {
                $course = isset($data['course']) ? $data['course'] : null;
                $year = isset($data['year']) ? $data['year'] : null;
                
                $stmt = $db->prepare("UPDATE students SET first_name = ?, last_name = ?, course = ?, year = ? WHERE user_id = ?");
                $stmt->execute([$data['first_name'], $data['last_name'], $course, $year, $user_id]);
            } else if ($user['role'] === 'staff') {
                $department = isset($data['department']) ? $data['department'] : '';
                
                $stmt = $db->prepare("UPDATE staff SET first_name = ?, last_name = ?, department = ? WHERE user_id = ?");
                $stmt->execute([$data['first_name'], $data['last_name'], $department, $user_id]);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            Response::error("Failed to update profile: " . $e->getMessage(), 500);
        }

        // Fetch updated profile
        $updatedUser = $userModel->findById($user_id);
        $profile = null;
        if ($updatedUser['role'] === 'student' || $updatedUser['role'] === 'rep') {
            $stmt = $db->prepare("SELECT first_name, last_name, course, year FROM students WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        } else if ($updatedUser['role'] === 'staff') {
            $stmt = $db->prepare("SELECT first_name, last_name, department FROM staff WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $userData = [
            'id' => $updatedUser['id'],
            'enrollment_no' => $updatedUser['enrollment_no'],
            'email' => $updatedUser['email'],
            'role' => $updatedUser['role'],
            'phone_number' => $updatedUser['phone_number']
        ];

        if ($profile) {
            $userData = array_merge($userData, $profile);
        }

        // Re-generate token
        require_once __DIR__ . '/../utils/JWT.php';
        $token = JWT::generate([
            'id' => $updatedUser['id'],
            'role' => $updatedUser['role']
        ]);

        Response::success("Profile updated successfully", [
            'token' => $token,
            'user' => $userData
        ]);
    }
}
?>
