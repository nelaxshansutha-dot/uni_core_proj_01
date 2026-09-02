<?php
namespace Controllers;

use Models\OtpVerification;
use Firebase\JWT\JWT;
use Middleware\AuthMiddleware;
use Exception;
use Utils\Validator;

class AuthController {
    public function register() {
        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        
        if (!$this->validatePayload($data, [
            'fname'        => "required|string|maxLength:100|regex:/^[A-Za-z][A-Za-z .'-]*$/D",
            'lname'        => "required|string|maxLength:100|regex:/^[A-Za-z][A-Za-z .'-]*$/D",
            'email'        => 'required|string|email|maxLength:150',
            'password'     => 'required|string|minLength:6|maxLength:72',
            'phoneNum'     => 'required|phone',
            'role'         => 'required|string|in:student,staff',
            'enrollmentNo' => 'requiredIf:role,student|nullable|string|maxLength:50'
        ])) {
            return;
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
                $otp      = $otpModel->generate($userID);
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
        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        
        if (!$this->validatePayload($data, [
            'identifier' => 'required|string|maxLength:150',
            'password'   => 'required|string|maxLength:255',
            'role'       => 'required|string|in:student,staff,course_representative,admin'
        ])) {
            return;
        }

        $identifier = $data['identifier'] ?? '';
        $role       = $data['role']       ?? 'student';
        $password   = $data['password']   ?? '';

        $user = \Models\User::loadByIdentifier($identifier, $role);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials or role mismatch.']);
            return;
        }

        try {
            if ($user->login($password)) {
                $payload = [
                    'userID' => $user->getUserID(),
                    'role'   => $user->getRole(),
                    'jti'    => uniqid('jwt_', true),
                    'iat'    => time(),
                    'exp'    => time() + 3600 * 24
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

                $token   = JWT::encode($payload, AuthMiddleware::getSecretKey(), 'HS256');
                $userDao = new \DAO\UserDAO();
                $userRow = $userDao->getUserById($user->getUserID());

                $userObj = [
                    'userID'                         => $userRow['userID'],
                    'first_name'                     => $userRow['fname'],
                    'last_name'                      => $userRow['lname'],
                    'email'                          => $userRow['email'],
                    'role'                           => $user->getRole(),
                    'phone_number'                   => $userRow['phoneNum'],
                    'lost_item_sms_notification'     => $userRow['lost_item_sms_notification'] ?? 0,
                    'peer_learning_app_notification' => $userRow['peer_learning_app_notification'] ?? 1,
                    'has_seen_lost_item_popup'       => $userRow['has_seen_lost_item_popup'] ?? 0
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
                    $courseRepDao = new \DAO\CourseRepresentativeDAO();
                    $isFirstLogin = $courseRepDao->getIsFirstLogin($user->getUserID());
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
        AuthMiddleware::authenticate();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    }

   
    public function forceChangeRepPassword() {
        $data     = json_decode(file_get_contents("php://input"), true) ?? [];

        if (!$this->validatePayload($data, [
            'user_id'      => 'required|positiveInteger',
            'new_password' => 'required|string|minLength:6|maxLength:72'
        ], true)) {
            return;
        }

        $userID   = $data['user_id']      ?? null;
        $newPass  = $data['new_password'] ?? '';

        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $courseRepDao = new \DAO\CourseRepresentativeDAO();
        $ok = $courseRepDao->forceChangePassword($userID, $hash);

        if ($ok) {
            echo json_encode(['status' => 'success', 'message' => 'Password updated successfully. You can now log in.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update password. Please contact your admin.']);
        }
    }

    public function verifyOtp() {
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        if (!$this->validatePayload($data, [
            'user_id' => 'required|positiveInteger',
            'otp'     => 'required|string|regex:/^[0-9]{6}$/D'
        ])) {
            return;
        }

        $userID = $data['user_id'] ?? null;
        $otp    = $data['otp']     ?? '';

        $userDao = new \DAO\UserDAO();
        $userData = $userDao->getUserById($userID);

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
        $data   = json_decode(file_get_contents("php://input"), true) ?? [];

        if (!$this->validatePayload($data, [
            'user_id' => 'required|positiveInteger'
        ])) {
            return;
        }

        $userID = $data['user_id'] ?? null;

        $userDao = new \DAO\UserDAO();
        $userData = $userDao->getUserById($userID);

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
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        if (!$this->validatePayload($data, [
            'email' => 'required|string|email|maxLength:150'
        ], true)) {
            return;
        }

        $email = $data['email'] ?? '';

        $userDao = new \DAO\UserDAO();
        $userID = $userDao->getUserIdByEmail($email);

        if (!$userID) {
            echo json_encode(['status' => 'success', 'data' => ['user_id' => null, 'email' => $email]]);
            return;
        }

        $otpModel = new OtpVerification();
        $otp      = $otpModel->generate($userID);
        $mailSent = \Utils\MailService::sendOTP($email, $otp);

        if ($mailSent) {
            echo json_encode(['status' => 'success', 'data' => ['user_id' => $userID, 'email' => $email]]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP email.']);
        }
    }

    public function verifyResetOtp() {
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        if (!$this->validatePayload($data, [
            'user_id' => 'required|positiveInteger',
            'otp'     => 'required|string|regex:/^[0-9]{6}$/D'
        ], true)) {
            return;
        }

        $userID = $data['user_id'] ?? null;
        $otp    = $data['otp']     ?? '';

        $userDao = new \DAO\UserDAO();
        $userData = $userDao->getUserById($userID);

        if (!$userData) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user.']);
            return;
        }

        $user = \Models\User::createInstanceFromRole($userData['role'], $userData);

        if ($user->verifyOTP($otp)) {
            $resetToken = hash('sha256', $userID . $otp . time());
            echo json_encode(['status' => 'success', 'data' => ['reset_token' => $resetToken]]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP.']);
        }
    }

    public function resetPassword() {
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        if (!$this->validatePayload($data, [
            'user_id'          => 'required|positiveInteger',
            'reset_token'      => 'required|string|regex:/^[a-f0-9]{64}$/D',
            'new_password'     => 'required|string|minLength:6|maxLength:72',
            'confirm_password' => 'required|string|same:new_password'
        ], true)) {
            return;
        }

        $userID      = $data['user_id']      ?? null;
        $resetToken  = $data['reset_token']  ?? '';
        $newPassword = $data['new_password'] ?? '';

        $hash       = password_hash($newPassword, PASSWORD_BCRYPT);
        $userDao    = new \DAO\UserDAO();
        $success    = $userDao->updatePassword($userID, $hash);

        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Password reset successfully. You can now login.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to reset password.']);
        }
    }

    public function getProfile() {
        $decoded = AuthMiddleware::authenticate();
        if (!$decoded) return;
        
        $userDao = new \DAO\UserDAO();
        $userDao->ensureProfileColumnsExist();
        
        $userRow = $userDao->getUserById($decoded->userID);
        
        if ($userRow) {
            $userData = [
                'userID'                         => $userRow['userID'],
                'first_name'                     => $userRow['fname'],
                'last_name'                      => $userRow['lname'],
                'email'                          => $userRow['email'],
                'role'                           => $decoded->role,
                'phone_number'                   => $userRow['phoneNum'],
                'lost_item_sms_notification'     => $userRow['lost_item_sms_notification'],
                'peer_learning_app_notification' => $userRow['peer_learning_app_notification'],
                'has_seen_lost_item_popup'       => $userRow['has_seen_lost_item_popup']
            ];

            // Fetch staffID for staff role
            if ($decoded->role === 'staff') {
                $staffDao = new \DAO\StaffDAO();
                $staffID = $staffDao->getStaffIDByUserId($decoded->userID);
                if ($staffID) $userData['staff_id'] = $staffID;
            }

            // Fetch adminID for admin role
            if ($decoded->role === 'admin') {
                $adminDao = new \DAO\AdminDAO();
                $adminID = $adminDao->getAdminIDByUserId($decoded->userID);
                if ($adminID) $userData['admin_id'] = $adminID;
            }

            // Fetch enrollmentNo for students and course representatives
            if ($decoded->role === 'student' || $decoded->role === 'course_representative') {
                $studentDao = new \DAO\StudentDAO();
                $enrollmentNo = $studentDao->getEnrollmentNoByUserId($decoded->userID);
                if ($enrollmentNo) $userData['enrollment_no'] = $enrollmentNo;
            }

            // Fetch rep_id_string for course representatives
            if ($decoded->role === 'course_representative') {
                $courseRepDao = new \DAO\CourseRepresentativeDAO();
                $repID = $courseRepDao->getRepIdStringByUserId($decoded->userID);
                if ($repID) $userData['rep_id'] = $repID;
            }

            echo json_encode(['success' => true, 'data' => $userData]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    }

    public function updateProfile() {
        $decoded = AuthMiddleware::authenticate();
        if (!$decoded) return;

        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        if (!$this->validatePayload($data, [
            'first_name'                     => "required|string|maxLength:100|regex:/^[A-Za-z][A-Za-z .'-]*$/D",
            'last_name'                      => "required|string|maxLength:100|regex:/^[A-Za-z][A-Za-z .'-]*$/D",
            'email'                          => 'nullable|string|email|maxLength:150',
            'phone_number'                   => 'required|phone',
            'lost_item_sms_notification'     => 'nullable|boolean',
            'peer_learning_app_notification' => 'nullable|boolean',
            'old_password'                   => 'requiredWith:new_password|nullable|string|maxLength:255',
            'new_password'                   => 'nullable|string|minLength:6|maxLength:72',
            'confirm_password'               => 'requiredWith:new_password|nullable|string|same:new_password'
        ], true)) {
            return;
        }

        $userDao = new \DAO\UserDAO();
        $userDao->ensureProfileColumnsExist();
        
        $fname    = $data['first_name']  ?? '';
        $lname    = $data['last_name']   ?? '';
        $phoneNum = $data['phone_number'] ?? '';
        $smsPref  = $data['lost_item_sms_notification']     ?? 0;
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
            $userRow = $userDao->getUserById($decoded->userID);

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
                $updatedUser = $userDao->getUserById($decoded->userID);
                
                $userData = [
                    'userID'                         => $updatedUser['userID'],
                    'first_name'                     => $updatedUser['fname'],
                    'last_name'                      => $updatedUser['lname'],
                    'email'                          => $updatedUser['email'],
                    'role'                           => $decoded->role,
                    'phone_number'                   => $updatedUser['phoneNum'],
                    'lost_item_sms_notification'     => $updatedUser['lost_item_sms_notification'],
                    'peer_learning_app_notification' => $updatedUser['peer_learning_app_notification']
                ];
                
                // Fetch enrollmentNo for students and course representatives
                if ($decoded->role === 'student' || $decoded->role === 'course_representative') {
                    $studentDao = new \DAO\StudentDAO();
                    $enrollmentNo = $studentDao->getEnrollmentNoByUserId($decoded->userID);
                    if ($enrollmentNo) $userData['enrollment_no'] = $enrollmentNo;
                }

                // Fetch adminID for admin role
                if ($decoded->role === 'admin') {
                    $adminDao = new \DAO\AdminDAO();
                    $adminID = $adminDao->getAdminIDByUserId($decoded->userID);
                    if ($adminID) $userData['admin_id'] = $adminID;
                }

                // Fetch staffID for staff role
                if ($decoded->role === 'staff') {
                    $staffDao = new \DAO\StaffDAO();
                    $staffID = $staffDao->getStaffIDByUserId($decoded->userID);
                    if ($staffID) $userData['staff_id'] = $staffID;
                }
                
                // Fetch rep_id for course representative role
                if ($decoded->role === 'course_representative') {
                    $courseRepDao = new \DAO\CourseRepresentativeDAO();
                    $repID = $courseRepDao->getRepIdStringByUserId($decoded->userID);
                    if ($repID) $userData['rep_id'] = $repID;
                }
                
                // Generate a fresh token matching login structure
                $payload = [
                    'userID' => $updatedUser['userID'],
                    'role'   => $decoded->role,
                    'jti'    => uniqid('jwt_', true),
                    'iat'    => time(),
                    'exp'    => time() + 3600 * 24
                ];
                if (isset($userData['enrollment_no'])) $payload['enrollmentNo'] = $userData['enrollment_no'];
                if (isset($userData['rep_id']))        $payload['repID']        = $userData['rep_id'];
                if (isset($userData['admin_id']))      $payload['adminID']      = $userData['admin_id'];
                if (isset($userData['staff_id']))      $payload['staffID']      = $userData['staff_id'];
                
                $jwt = JWT::encode($payload, AuthMiddleware::getSecretKey(), 'HS256');

                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Profile updated successfully',
                    'data'    => [
                        'token' => $jwt,
                        'user'  => $userData
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
            }
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function validatePayload(array $data, array $rules, bool $statusResponse = false): bool {
        $validator = new Validator($data);
        $validator->validate($rules);

        if ($validator->passes()) {
            return true;
        }

        $response = [
            'message' => $validator->getFirstError(),
            'errors'  => $validator->getErrors()
        ];

        if ($statusResponse) {
            $response['status'] = 'error';
        } else {
            $response['success'] = false;
        }

        echo json_encode($response);
        return false;
    }
}
