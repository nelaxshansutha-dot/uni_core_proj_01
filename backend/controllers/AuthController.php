<?php
namespace Controllers;

use Models\OtpVerification;
use Firebase\JWT\JWT;
use Middleware\AuthMiddleware;
use Exception;

class AuthController {
    public function register() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['email'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid payload']);
            return;
        }

        if (isset($data['phoneNum']) && !empty($data['phoneNum'])) {
            if (!preg_match('/^[0-9]{10}$/', $data['phoneNum'])) {
                echo json_encode(['success' => false, 'message' => 'Phone number must be exactly 10 digits.']);
                return;
            }
        }
         
        $role = $data['role'] ?? 'student';
        $data['hash_password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $user = \Models\User::createInstanceFromRole($role);
        $user->hydrateFromRequest($data);
        $user->setRole($role);
        try {
            $userID = $user->register();
            if ($userID) {
                $otpModel = new OtpVerification();
                $otp = $otpModel->generate($userID);
                
                // Send the OTP via email
                $mailSent = \Utils\MailService::sendOTP($data['email'], $otp);
                
                if ($mailSent) {
                    echo json_encode(['success' => true, 'message' => 'Registration successful. OTP sent.', 'userID' => $userID]);
                } else {
                    echo json_encode(['success' => true, 'message' => 'Registration successful, but failed to send OTP email. Please try logging in to trigger a new OTP.', 'userID' => $userID, 'otp_debug' => $otp]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Registration failed internally.']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        $identifier = $data['identifier'] ?? '';
        $role = $data['role'] ?? 'student';
        $password = $data['password'] ?? '';

        $user = \Models\User::loadByIdentifier($identifier, $role);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials or role mismatch.']);
            return;
        }

        try {
            if ($user->login($password)) {
                $payload = [
                    'userID' => $user->getUserID(),
                    'role' => $user->getRole(),
                    'jti' => uniqid('jwt_', true),
                    'iat' => time(),
                    'exp' => time() + 3600 * 24 // 24 hrs
                ];
                if (method_exists($user, 'getEnrollmentNo')) {
                    $payload['enrollmentNo'] = $user->getEnrollmentNo();
                }
                if (method_exists($user, 'getRepID')) {
                    $payload['repID'] = $user->getRepID();
                }
                if (method_exists($user, 'getAdminID')) {
                    $payload['adminID'] = $user->getAdminID();
                }
                if (method_exists($user, 'getStaffID')) {
                    $payload['staffID'] = $user->getStaffID();
                }

                $token = JWT::encode($payload, AuthMiddleware::getSecretKey(), 'HS256');
                $db = \Config\Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT * FROM users WHERE userID = :uid");
                $stmt->execute([':uid' => $user->getUserID()]);
                $userRow = $stmt->fetch(\PDO::FETCH_ASSOC);

                $userObj = [
                    'userID'       => $userRow['userID'],
                    'first_name'   => $userRow['fname'],
                    'last_name'    => $userRow['lname'],
                    'email'        => $userRow['email'],
                    'role'         => $user->getRole(),
                    'phone_number' => $userRow['phoneNum'],
                    'lost_item_sms_notification' => $userRow['lost_item_sms_notification'] ?? 0,
                    'peer_learning_app_notification' => $userRow['peer_learning_app_notification'] ?? 1,
                    'has_seen_lost_item_popup' => $userRow['has_seen_lost_item_popup'] ?? 0
                ];
                if (method_exists($user, 'getEnrollmentNo')) {
                    $userObj['enrollment_no'] = $user->getEnrollmentNo();
                }
                if (method_exists($user, 'getAdminID')) {
                    $userObj['admin_id'] = $user->getAdminID();
                }
                if (method_exists($user, 'getStaffID')) {
                    $userObj['staff_id'] = $user->getStaffID();
                }

                $isFirstLogin = false;
                if ($user->getRole() === 'course_representative') {
                    $repStmt = $db->prepare("SELECT is_first_login FROM course_representative WHERE userID = :uid LIMIT 1");
                    $repStmt->execute([':uid' => $user->getUserID()]);
                    $repRow = $repStmt->fetch(\PDO::FETCH_ASSOC);
                    $isFirstLogin = $repRow ? (bool)$repRow['is_first_login'] : false;
                }

                echo json_encode([
                    'success'        => true,
                    'token'          => $token,
                    'user'           => $userObj,
                    'is_first_login' => $isFirstLogin
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function logout() {
        $decoded = AuthMiddleware::authenticate();
        switch ($decoded->role) {
            case 'admin':
                 $user = new \Models\Admin(); 
                 break;
            case 'staff':
                 $user = new \Models\Staff(); 
                 break;
            case 'course_representative': 
                $user = new \Models\CourseRepresentative();
                 break;
            case 'student':
            default:
             $user = new \Models\Student();
              break;
        }
        $user->logout($decoded->jti, $decoded->exp);
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    }

   
    public function forceChangeRepPassword() {
        $data     = json_decode(file_get_contents("php://input"), true);
        $userID   = $data['user_id'] ?? null;
        $newPass  = $data['new_password'] ?? '';

        if (!$userID || strlen($newPass) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'User ID and a password of at least 6 characters are required.']);
            return;
        }

        $db   = \Config\Database::getInstance()->getConnection();
        $hash = password_hash($newPass, PASSWORD_BCRYPT);

        // Update the password in course_representative table and mark first login as done
        $stmt = $db->prepare(
            "UPDATE course_representative SET hash_password = :hash, is_first_login = 0 WHERE userID = :uid"
        );
        $ok = $stmt->execute([':hash' => $hash, ':uid' => $userID]);

        if ($ok && $stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Password updated successfully. You can now log in.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update password. Please contact your admin.']);
        }
    }

    public function verifyOtp() {
        $data = json_decode(file_get_contents("php://input"), true);
        $userID = $data['user_id'] ?? null;
        $otp    = $data['otp'] ?? '';

        if (!$userID) {
            echo json_encode(['success' => false, 'message' => 'User ID is required.']);
            return;
        }

   
        $db = \Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE userID = :uid");
        $stmt->execute([':uid' => $userID]);
        $userData = $stmt->fetch();

        if (!$userData) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            return;
        }

        $user = \Models\User::createInstanceFromRole($userData['role'], $userData);

        if ($user->verifyOTP($otp)) {
            echo json_encode(['success' => true, 'message' => 'Account verified.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP.']);
        }
    }

    public function resendOtp() {
        $data   = json_decode(file_get_contents("php://input"), true);
        $userID = $data['user_id'] ?? null;

        if (!$userID) {
            echo json_encode(['success' => false, 'message' => 'User ID is required.']);
            return;
        }

        $db = \Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE userID = :uid");
        $stmt->execute([':uid' => $userID]);
        $userData = $stmt->fetch();

        if (!$userData) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            return;
        }

        $otpModel = new OtpVerification();
        $otp      = $otpModel->generate($userID);
        $mailSent = \Utils\MailService::sendOTP($userData['email'], $otp);

        if ($mailSent) {
            echo json_encode(['success' => true, 'message' => 'OTP resent successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to resend OTP email.', 'otp_debug' => $otp]);
        }
    }

    public function forgotPassword() {
        $data = json_decode(file_get_contents("php://input"), true);
        $email = $data['email'] ?? '';

        if (!$email) {
            echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
            return;
        }

        $db = \Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT userID FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $userID = $stmt->fetchColumn();

        if (!$userID) {
            echo json_encode(['status' => 'success', 'data' => ['user_id' => null, 'email' => $email]]);
            return;
        }

        $otpModel = new OtpVerification();
        $otp = $otpModel->generate($userID);
        $mailSent = \Utils\MailService::sendOTP($email, $otp);

        if ($mailSent) {
            echo json_encode(['status' => 'success', 'data' => ['user_id' => $userID, 'email' => $email]]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP email.']);
        }
    }

    public function verifyResetOtp() {
        $data = json_decode(file_get_contents("php://input"), true);
        $userID = $data['user_id'] ?? null;
        $otp = $data['otp'] ?? '';

        if (!$userID || !$otp) {
            echo json_encode(['status' => 'error', 'message' => 'User ID and OTP are required.']);
            return;
        }

        $db = \Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE userID = :uid");
        $stmt->execute([':uid' => $userID]);
        $userData = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$userData) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user.']);
            return;
        }

        $user = \Models\User::createInstanceFromRole($userData['role'], $userData);

        if ($user->verifyOTP($otp)) {
            // Generate a temporary reset token (for demo purposes, a simple hash)
            $resetToken = hash('sha256', $userID . $otp . time());
            
          
            echo json_encode(['status' => 'success', 'data' => ['reset_token' => $resetToken]]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP.']);
        }
    }

    public function resetPassword() {
        $data = json_decode(file_get_contents("php://input"), true);
        $userID = $data['user_id'] ?? null;
        $resetToken = $data['reset_token'] ?? '';
        $newPassword = $data['new_password'] ?? '';

        if (!$userID || !$resetToken || !$newPassword) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
            return;
        }

        $db = \Config\Database::getInstance()->getConnection();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateStmt = $db->prepare("UPDATE users SET hash_password = :hash WHERE userID = :uid");
        $success = $updateStmt->execute([':hash' => $hash, ':uid' => $userID]);

        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Password reset successfully. You can now login.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to reset password.']);
        }
    }

    public function getProfile() {
        $decoded = AuthMiddleware::authenticate();
        if (!$decoded) return;
        
        $db = \Config\Database::getInstance()->getConnection();
        
        // Ensure columns exist to prevent crash during demo
        try {
            $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS lost_item_sms_notification TINYINT(1) DEFAULT 0");
            $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS peer_learning_app_notification TINYINT(1) DEFAULT 1");
            $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS has_seen_lost_item_popup TINYINT(1) DEFAULT 0");
        } catch (\Exception $e) {}
        
        $stmt = $db->prepare("SELECT * FROM users WHERE userID = :uid");
        $stmt->execute([':uid' => $decoded->userID]);
        $userRow = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($userRow) {
            $userData = [
                'userID' => $userRow['userID'],
                'first_name' => $userRow['fname'],
                'last_name' => $userRow['lname'],
                'email' => $userRow['email'],
                'role' => $decoded->role,
                'phone_number' => $userRow['phoneNum'],
                'lost_item_sms_notification' => $userRow['lost_item_sms_notification'],
                'peer_learning_app_notification' => $userRow['peer_learning_app_notification'],
                'has_seen_lost_item_popup' => $userRow['has_seen_lost_item_popup']
            ];

            // Fetch staffID for staff role
            if ($decoded->role === 'staff') {
                $staffStmt = $db->prepare("SELECT staffID FROM staff WHERE userID = :uid LIMIT 1");
                $staffStmt->execute([':uid' => $decoded->userID]);
                $staffRow = $staffStmt->fetch(\PDO::FETCH_ASSOC);
                if ($staffRow) {
                    $userData['staff_id'] = $staffRow['staffID'];
                }
            }

            // Fetch adminID for admin role
            if ($decoded->role === 'admin') {
                $adminStmt = $db->prepare("SELECT adminID FROM admin WHERE userID = :uid LIMIT 1");
                $adminStmt->execute([':uid' => $decoded->userID]);
                $adminRow = $adminStmt->fetch(\PDO::FETCH_ASSOC);
                if ($adminRow) {
                    $userData['admin_id'] = $adminRow['adminID'];
                }
            }

            // Fetch enrollmentNo for students and course representatives
            if ($decoded->role === 'student' || $decoded->role === 'course_representative') {
                $studentStmt = $db->prepare("SELECT enrollmentNo FROM student WHERE userID = :uid LIMIT 1");
                $studentStmt->execute([':uid' => $decoded->userID]);
                $studentRow = $studentStmt->fetch(\PDO::FETCH_ASSOC);
                if ($studentRow) {
                    $userData['enrollment_no'] = $studentRow['enrollmentNo'];
                }
            }

            // Fetch rep_id_string for course representatives
            if ($decoded->role === 'course_representative') {
                $repStmt = $db->prepare("SELECT rep_id_string FROM course_representative WHERE userID = :uid LIMIT 1");
                $repStmt->execute([':uid' => $decoded->userID]);
                $repRow = $repStmt->fetch(\PDO::FETCH_ASSOC);
                if ($repRow) {
                    $userData['rep_id'] = $repRow['rep_id_string'];
                }
            }

            echo json_encode(['success' => true, 'data' => $userData]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    }

    public function updateProfile() {
        $decoded = AuthMiddleware::authenticate();
        if (!$decoded) return;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
            return;
        }

        $db = \Config\Database::getInstance()->getConnection();
        
        try {
            $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS lost_item_sms_notification TINYINT(1) DEFAULT 0");
            $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS peer_learning_app_notification TINYINT(1) DEFAULT 1");
            $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS has_seen_lost_item_popup TINYINT(1) DEFAULT 0");
        } catch (\Exception $e) {}
        
        $fname = $data['first_name'] ?? '';
        $lname = $data['last_name'] ?? '';
        $phoneNum = $data['phone_number'] ?? '';
        
        if (!preg_match('/^[0-9]{10}$/', $phoneNum)) {
            echo json_encode(['status' => 'error', 'message' => 'Phone number must be exactly 10 digits and contain only numbers.']);
            return;
        }
        
        $smsPref = $data['lost_item_sms_notification'] ?? 0;
        $peerPref = $data['peer_learning_app_notification'] ?? 1;

        $user = \Models\User::createInstanceFromRole($decoded->role);
        $user
            ->setUserID($decoded->userID)
            ->setFname($fname)
            ->setLname($lname)
            ->setPhoneNum($phoneNum)
            ->setLostItemSmsNotification($smsPref)
            ->setPeerLearningAppNotification($peerPref);

        if (!empty($data['new_password'])) {
            // Verify old password
            $stmt = $db->prepare("SELECT hash_password FROM users WHERE userID = :uid");
            $stmt->execute([':uid' => $decoded->userID]);
            $userRow = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!password_verify($data['old_password'], $userRow['hash_password'])) {
                echo json_encode(['status' => 'error', 'message' => 'Incorrect current password.']);
                return;
            }

            $user->setHashPassword(password_hash($data['new_password'], PASSWORD_BCRYPT));
        }
        
        try {
            $success = $user->updateProfile();

            if ($success) {
                // Fetch updated user to return
                $stmt = $db->prepare("SELECT * FROM users WHERE userID = :uid");
                $stmt->execute([':uid' => $decoded->userID]);
                $updatedUser = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                // Keep token same, just return updated user data (matching what Login does)
                $userData = [
                    'userID' => $updatedUser['userID'],
                    'first_name' => $updatedUser['fname'],
                    'last_name' => $updatedUser['lname'],
                    'email' => $updatedUser['email'],
                    'role' => $decoded->role,
                    'phone_number' => $updatedUser['phoneNum'],
                    'lost_item_sms_notification' => $updatedUser['lost_item_sms_notification'],
                    'peer_learning_app_notification' => $updatedUser['peer_learning_app_notification']
                ];
                
                // Fetch enrollmentNo for students and course representatives
                if ($decoded->role === 'student' || $decoded->role === 'course_representative') {
                    $studentStmt = $db->prepare("SELECT enrollmentNo FROM student WHERE userID = :uid LIMIT 1");
                    $studentStmt->execute([':uid' => $decoded->userID]);
                    $studentRow = $studentStmt->fetch(\PDO::FETCH_ASSOC);
                    if ($studentRow) {
                        $userData['enrollment_no'] = $studentRow['enrollmentNo'];
                    }
                }

                if (isset($updatedUser['staffID'])) $userData['staff_id'] = $updatedUser['staffID'];
                if (isset($updatedUser['adminID'])) $userData['admin_id'] = $updatedUser['adminID'];
                
                // Generate a fresh token matching login structure
                $payload = [
                    'userID' => $updatedUser['userID'],
                    'role' => $decoded->role,
                    'jti' => uniqid('jwt_', true),
                    'iat' => time(),
                    'exp' => time() + 3600 * 24 // 24 hrs
                ];
                if (isset($userData['enrollment_no'])) $payload['enrollmentNo'] = $userData['enrollment_no'];
                if (isset($updatedUser['repID'])) $payload['repID'] = $updatedUser['repID'];
                if (isset($updatedUser['adminID'])) $payload['adminID'] = $updatedUser['adminID'];
                if (isset($updatedUser['staffID'])) $payload['staffID'] = $updatedUser['staffID'];
                
                $jwt = JWT::encode($payload, AuthMiddleware::getSecretKey(), 'HS256');

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Profile updated successfully',
                    'data' => [
                        'token' => $jwt,
                        'user' => $userData
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
            }
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
