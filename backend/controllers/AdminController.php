<?php
namespace Controllers;

use Middleware\AuthMiddleware;
use Utils\Validator;

class AdminController {
    public function handleUsers($method, $id = null) {
        AuthMiddleware::authenticate(['admin']);
        
        if ($method === 'GET') {
            try {
                $q = $_GET['q'] ?? '';
                $role = $_GET['role'] ?? 'all';
                if ($role === '') $role = 'all'; 

                if (!$this->validatePayload(
                    ['q' => $q, 'role' => $role],
                    [
                        'q' => 'nullable|string|maxLength:100',
                        'role' => 'required|string|in:all,student,staff,rep,course_representative,admin'
                    ]
                )) {
                    return;
                }
                
                $admin = new \Models\Admin();
                $users = $admin->manageUsers($role, $q);
                
                echo json_encode(['success' => true, 'data' => $users]);
            } catch (\Exception $e) {
                error_log("handleUsers error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'An error occurred while fetching users.']);
            }
            return;
        }
    }
        
    public function createUser() {
        AuthMiddleware::authenticate(['admin']);
        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        
        if (!$this->validatePayload($data, [
            'email' => 'required|string|email|maxLength:150',
            'first_name' => "required|string|maxLength:100|regex:/^[A-Za-z][A-Za-z .'-]*$/D",
            'last_name' => "required|string|maxLength:100|regex:/^[A-Za-z][A-Za-z .'-]*$/D",
            'phone_number' => 'required|phone',
            'password' => 'required|string|minLength:6|maxLength:72',
            'role' => 'required|string|in:student,staff,course_representative,admin',
            'enrollment_no' => 'requiredIf:role,student|requiredIf:role,course_representative|nullable|string|maxLength:50',
            'course' => 'nullable|positiveInteger',
            'year' => 'nullable|integer|min:1|max:4'
        ])) {
            return;
        }

        $role = $data['role'] ?? 'student';
        $data['hash_password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Map frontend fields to backend model fields
        $data['fname'] = $data['first_name'];
        $data['lname'] = $data['last_name'];
        $data['phoneNum'] = $data['phone_number'];
        $data['enrollmentNo'] = $data['enrollment_no'] ?? null; // Student specific
        $data['courseID'] = isset($data['course']) && !empty($data['course']) ? $data['course'] : 1; // Default to 1 if empty
        $data['std_year'] = isset($data['year']) && !empty($data['year']) ? $data['year'] : 1;
        
        try {
            switch ($role) {
                case 'admin': $user = new \Models\Admin(); break;
                case 'staff': $user = new \Models\Staff(); break;
                case 'course_representative': $user = new \Models\CourseRepresentative(); break;
                case 'student':
                default: $user = new \Models\Student(); break;
            }
            $user->hydrateFromRequest($data);
            $user->setRole($role);
            $userID = $user->register();
            
            // Auto verify admin created users
            $userDao = new \DAO\UserDAO();
            $userDao->markUserVerified($userID);
            
            echo json_encode(['success' => true, 'message' => 'User created successfully']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateUser($id) {
        AuthMiddleware::authenticate(['admin']);
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        if (!$this->validatePayload(array_merge($data, ['user_id' => $id]), [
            'user_id' => 'required|positiveInteger',
            'email' => 'required|string|email|maxLength:150',
            'first_name' => "required|string|maxLength:100|regex:/^[A-Za-z][A-Za-z .'-]*$/D",
            'last_name' => "required|string|maxLength:100|regex:/^[A-Za-z][A-Za-z .'-]*$/D",
            'phone_number' => 'required|phone'
        ])) {
            return;
        }

        $userDao = new \DAO\UserDAO();
        $success = $userDao->updateBasicProfile(
            $id,
            $data['first_name'],
            $data['last_name'],
            $data['phone_number'],
            $data['email']
        );
        
        if ($success) echo json_encode(['success' => true]);
        else echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }

    public function toggleUserStatus($id) {
        AuthMiddleware::authenticate(['admin']);
        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        
        // Allow un-assigning reps
        if (is_string($id) && strpos($id, 'rep_') === 0) {
            if (!$this->validatePayload(
                array_merge($data, ['rep_target' => $id]),
                [
                    'rep_target' => 'required|string|regex:/^rep_[1-9][0-9]*$/D',
                    'is_active' => 'required|boolean'
                ]
            )) {
                return;
            }

            $uid = str_replace('rep_', '', $id);
            $userDao = new \DAO\UserDAO();
            $userDao->updateRole($uid, 'student');
            
            $courseRepDao = new \DAO\CourseRepresentativeDAO();
            $courseRepDao->deleteRepByUserId($uid);
            
            echo json_encode(['success' => true]);
            return;
        }

        if (!$this->validatePayload(array_merge($data, ['user_id' => $id]), [
            'user_id' => 'required|positiveInteger',
            'is_active' => 'required|boolean',
            'reason' => 'requiredIf:is_active,false|nullable|string|maxLength:1000'
        ])) {
            return;
        }

        $isActiveValue = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $isActive = $isActiveValue ? 1 : 0;
        $reason = $data['reason'] ?? 'No reason provided by administrator.';
        $userDao = new \DAO\UserDAO();
        
        if ($isActive === 0) {
            $user = $userDao->getUserById($id);
            
            if ($user && !empty($user['email'])) {
                require_once __DIR__ . '/../utils/MailService.php';
                \Utils\MailService::sendDeactivationEmail($user['email'], $reason);
            }
        }
        
        $success = $userDao->updateActiveStatus($id, $isActive);
        
        echo json_encode(['success' => $success]);
    }
    
    public function searchStudents() {
        AuthMiddleware::authenticate(['admin']);
        $q = $_GET['q'] ?? '';

        if (!$this->validatePayload(['q' => $q], [
            'q' => 'required|string|maxLength:100'
        ])) {
            return;
        }

        $studentDao = new \DAO\StudentDAO();
        $students = $studentDao->searchStudents($q);
        
        echo json_encode(['success' => true, 'data' => $students]);
    }

    public function assignCourseRep() {
        AuthMiddleware::authenticate(['admin']);
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        if (!$this->validatePayload($data, [
            'user_id' => 'required|positiveInteger',
            'fname' => "required|string|maxLength:100|regex:/^[A-Za-z][A-Za-z .'-]*$/D",
            'lname' => "required|string|maxLength:100|regex:/^[A-Za-z][A-Za-z .'-]*$/D",
            'phone' => 'required|phone',
            'email' => 'required|string|email|maxLength:150',
            'rep_id' => 'required|string|maxLength:50|regex:/^[A-Za-z0-9_\/-]+$/D',
            'password' => 'required|string|minLength:6|maxLength:72',
            'course' => 'nullable|positiveInteger',
            'year' => 'nullable|integer|min:1|max:4'
        ])) {
            return;
        }
        
        try {
            $admin = new \Models\Admin();
            $result = $admin->assignCourseRep($data);
            echo json_encode($result);
        } catch (\Exception $e) {
            error_log("assignCourseRep error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while assigning rep.']);
        }
    }
    
    public function moderateContent() {
        AuthMiddleware::authenticate(['admin']);
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        if (!$this->validatePayload($data, [
            'content_type' => 'required|string|in:lost_item,marketplace,notes',
            'content_id' => 'required|positiveInteger',
            'status' => 'required|string|in:removed',
            'reason' => 'required|string|maxLength:1000'
        ])) {
            return;
        }

        $contentType = $data['content_type'] ?? '';
        $contentId = $data['content_id'] ?? null;
        $status = $data['status'] ?? '';
        $reason = $data['reason'] ?? '';

        if ($status === 'removed' && $contentId) {
            $ownerEmail = null;
            $title = '';

            if ($contentType === 'lost_item') {
                $lostItemDao = new \DAO\LostItemDAO();
                $row = $lostItemDao->getLostItemWithOwner($contentId);
                if ($row) {
                    $ownerEmail = $row['email'];
                    $title = $row['title'];
                }
                $lostItemDao->deleteByAdmin($contentId);

            } elseif ($contentType === 'marketplace') {
                $marketplaceDao = new \DAO\MarketplaceDAO();
                $row = $marketplaceDao->getMarketItemWithOwner($contentId);
                if ($row) {
                    $ownerEmail = $row['email'];
                    $title = $row['title'];
                }
                $marketplaceDao->deleteByAdmin($contentId);

            } elseif ($contentType === 'notes') {
                $notesDao = new \DAO\NotesDAO();
                $row = $notesDao->getNoteWithOwner($contentId);
                if ($row) {
                    $ownerEmail = $row['email'];
                    $title = $row['title'];
                }
                $notesDao->deleteByAdmin($contentId);
            }

            // Send notification email to content owner
            if ($ownerEmail) {
                try {
                    \Utils\MailService::sendModerationEmail($ownerEmail, $title, $contentType, $reason);
                } catch (\Exception $e) {
                    error_log("Failed to send moderation email: " . $e->getMessage());
                }
            }
        }
        
        echo json_encode(['success' => true]);
    }

    public function moderateReport() {
        AuthMiddleware::authenticate(['admin']);
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        if (!$this->validatePayload($data, [
            'report_id' => 'required|positiveInteger',
            'status' => 'required|string|maxLength:50'
        ])) {
            return;
        }

        echo json_encode(['success' => true]);
    }

    public function getDashboardStats() {
        AuthMiddleware::authenticate(['admin']);
        try {
            $admin = new \Models\Admin();
            $monitorData = $admin->monitorPlatform();
            echo json_encode(['success' => true, 'data' => $monitorData['stats']]);
        } catch (\Exception $e) {
            error_log("getDashboardStats error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while fetching stats.']);
        }
    }
    
    public function getContent() {
        AuthMiddleware::authenticate(['admin']);
        try {
            $admin = new \Models\Admin();
            $monitorData = $admin->monitorPlatform();
            echo json_encode(['success' => true, 'data' => $monitorData['content']]);
        } catch (\Exception $e) {
            error_log("getContent error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while fetching content.']);
        }
    }
    
    public function getReports() {
        AuthMiddleware::authenticate(['admin']);
        try {
            $admin = new \Models\Admin();
            $monitorData = $admin->monitorPlatform();
            echo json_encode(['success' => true, 'data' => $monitorData['reports']]);
        } catch (\Exception $e) {
            error_log("getReports error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while fetching reports.']);
        }
    }

    private function validatePayload(array $data, array $rules): bool {
        $validator = new Validator($data);
        $validator->validate($rules);

        if ($validator->passes()) {
            return true;
        }

        echo json_encode([
            'success' => false,
            'message' => $validator->getFirstError(),
            'errors' => $validator->getErrors()
        ]);
        return false;
    }
}
