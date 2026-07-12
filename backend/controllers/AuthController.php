<?php
namespace Controllers;
use Models\UserFactory;
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

        $role = $data['role'] ?? 'student';
        $data['hash_password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $user = UserFactory::create($role, $data);
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

        $user = UserFactory::loadByIdentifier($identifier, $role);
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
                    'role'         => $userRow['role'],
                    'phone_number' => $userRow['phoneNum'],
                    'lost_item_sms_notification' => $userRow['lost_item_sms_notification'] ?? 0,
                    'peer_learning_app_notification' => $userRow['peer_learning_app_notification'] ?? 1,
                    'has_seen_lost_item_popup' => $userRow['has_seen_lost_item_popup'] ?? 0
                ];
                if (method_exists($user, 'getEnrollmentNo')) {
                    $userObj['enrollment_no'] = $user->getEnrollmentNo();
                }
                echo json_encode(['success' => true, 'token' => $token, 'user' => $userObj]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function logout() {
        $decoded = AuthMiddleware::authenticate();
        $user = UserFactory::create($decoded->role);
        $user->logout($decoded->jti, $decoded->exp);
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    }

    public function verifyOtp() {
        $data = json_decode(file_get_contents("php://input"), true);
        $userID = $data['user_id'] ?? null;
        $otp    = $data['otp'] ?? '';

        if (!$userID) {
            echo json_encode(['success' => false, 'message' => 'User ID is required.']);
            return;
        }

        // Load user by ID directly
        $db = \Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE userID = :uid");
        $stmt->execute([':uid' => $userID]);
        $userData = $stmt->fetch();

        if (!$userData) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            return;
        }

        $user = \Models\UserFactory::create($userData['role'], $userData);

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

        $user = \Models\UserFactory::create($userData['role'], $userData);

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
                'role' => $userRow['role'],
                'phone_number' => $userRow['phoneNum'],
                'lost_item_sms_notification' => $userRow['lost_item_sms_notification'],
                'peer_learning_app_notification' => $userRow['peer_learning_app_notification'],
                'has_seen_lost_item_popup' => $userRow['has_seen_lost_item_popup']
            ];
            if (isset($userRow['enrollmentNo'])) $userData['enrollment_no'] = $userRow['enrollmentNo'];
            if (isset($userRow['staffID'])) $userData['staff_id'] = $userRow['staffID'];
            
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
        $smsPref = $data['lost_item_sms_notification'] ?? 0;
        $peerPref = $data['peer_learning_app_notification'] ?? 1;

        $query = "UPDATE users SET fname = :fname, lname = :lname, phoneNum = :phoneNum, lost_item_sms_notification = :smsPref, peer_learning_app_notification = :peerPref";
        $params = [
            ':fname' => $fname,
            ':lname' => $lname,
            ':phoneNum' => $phoneNum,
            ':smsPref' => $smsPref,
            ':peerPref' => $peerPref,
            ':uid' => $decoded->userID
        ];

        if (!empty($data['new_password'])) {
            // Verify old password
            $stmt = $db->prepare("SELECT hash_password FROM users WHERE userID = :uid");
            $stmt->execute([':uid' => $decoded->userID]);
            $userRow = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!password_verify($data['old_password'], $userRow['hash_password'])) {
                echo json_encode(['status' => 'error', 'message' => 'Incorrect current password.']);
                return;
            }

            $query .= ", hash_password = :hash";
            $params[':hash'] = password_hash($data['new_password'], PASSWORD_BCRYPT);
        }

        $query .= " WHERE userID = :uid";
        
        try {
            $stmt = $db->prepare($query);
            $success = $stmt->execute($params);

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
                    'role' => $updatedUser['role'],
                    'phone_number' => $updatedUser['phoneNum'],
                    'lost_item_sms_notification' => $updatedUser['lost_item_sms_notification'],
                    'peer_learning_app_notification' => $updatedUser['peer_learning_app_notification']
                ];
                
                if (isset($updatedUser['enrollmentNo'])) $userData['enrollment_no'] = $updatedUser['enrollmentNo'];
                if (isset($updatedUser['staffID'])) $userData['staff_id'] = $updatedUser['staffID'];
                
                // Generate a fresh token matching login structure
                $payload = [
                    'userID' => $updatedUser['userID'],
                    'role' => $updatedUser['role'],
                    'jti' => uniqid('jwt_', true),
                    'iat' => time(),
                    'exp' => time() + 3600 * 24 // 24 hrs
                ];
                if (isset($updatedUser['enrollmentNo'])) $payload['enrollmentNo'] = $updatedUser['enrollmentNo'];
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
