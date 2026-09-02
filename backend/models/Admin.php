<?php

namespace Models;

use PDO;
use Exception;

class Admin extends User {
    
    private $adminID;

    public function getAdminID() { return $this->adminID; }
    public function setAdminID($val) { $this->adminID = $val; return $this; }

    private $adminDAO;

    public function __construct() {
        parent::__construct();
        $this->adminDAO = new \DAO\AdminDAO();
    }

    public function hydrate(array $data = []): static {
        parent::hydrate($data);
        if (array_key_exists('adminID', $data)) {
            $this->setAdminID($data['adminID']);
        }
        return $this;
    }

    public function register() {
        $this->adminDAO->beginTransaction();
        try {
            if (!parent::register()) {
                throw new \Exception("Failed to register user");
            }
            if (empty($this->adminID)) {
                $this->adminID = uniqid('admin_');
            }
            $this->adminDAO->insertAdmin($this->adminID, $this->getUserID());
            $this->adminDAO->commit();
            return $this->getUserID();
        } catch (\Exception $e) {
            $this->adminDAO->rollBack();
            throw $e;
        }
    }

    public function manageUsers($role = 'all', $q = '') {
        $users = $this->adminDAO->manageUsers($role, $q);
        
        foreach ($users as &$u) {
            $u['is_active'] = (int)$u['is_active'];
            if ($u['role'] === 'course_representative') {
                $u['role'] = 'rep';
            }
        }
        
        return $users;
    }

    public function assignCourseRep(array $data) {
        try {
            $this->adminDAO->beginTransaction();

            $userID = $data['user_id'] ?? null;
            if (!$userID) {
                $this->adminDAO->rollBack();
                return ['success' => false, 'message' => 'User ID is required.'];
            }

            $student = $this->adminDAO->getStudentByUserId($userID);
            $enrollmentNo = $student ? $student['enrollmentNo'] : null;

            if (!$enrollmentNo) {
                $this->adminDAO->rollBack();
                return ['success' => false, 'message' => 'Student enrollment number not found.'];
            }

            // Auto-detect courseID from enrollment number (e.g. UWU/CST/23/088 → CST → courseID 1)
            $courseID = $student['courseID'] ?? null;
            $enrUpper = strtoupper(trim($enrollmentNo));
            $enrParts = explode('/', $enrUpper);
            if (!$courseID) {
                $courseCode = $enrParts[1] ?? '';
                if ($courseCode === 'CST') $courseID = 1;
            }

           
            $batchYear = isset($enrParts[2]) ? (int)$enrParts[2] : null;
            $currentYear = (int)date('Y');
            $currentMonth = (int)date('m');
            $academicYear = ($currentMonth < 10) ? $currentYear - 1 : $currentYear;
            $stdYear = $batchYear ? ($academicYear % 100 - $batchYear) : null;

            if ($courseID) {
                $this->adminDAO->updateStudentCourse($courseID, $stdYear, $userID);
            }

            // ─── DUPLICATE CHECK ──────────────────────────────────────────────────────
            // Block assignment if ANOTHER rep already exists for the same course + batch year.
            // Batch year is extracted from enrollmentNo (e.g. UWU/CST/23/088 → batch 23).
            if ($courseID && $batchYear) {
                $existing = $this->adminDAO->checkDuplicateRep($courseID, $userID, $batchYear);

                if ($existing) {
                    $this->adminDAO->rollBack();
                    return [
                        'success' => false,
                        'message' => "A Course Representative already exists for this course and batch year. "
                                   . "Existing rep: {$existing['fname']} {$existing['lname']} ({$existing['enrollmentNo']}). "
                                   . "Please un-assign the current rep first before assigning a new one."
                    ];
                }
            }
            // ─────────────────────────────────────────────────────────────────────────

            $repId = !empty($data['rep_id']) 
                ? strtolower(trim($data['rep_id'])) 
                : 'rep_' . strtolower($enrollmentNo);

            $password = $data['password'] ?? '';
            $hashPass = password_hash($password, PASSWORD_BCRYPT);

            $existRep = $this->adminDAO->getRepByUserId($userID);

            if ($existRep) {
                $this->adminDAO->updateRep($repId, $hashPass, $courseID, $userID);
            } else {
                $this->adminDAO->insertRep($userID, $enrollmentNo, $courseID, $repId, $hashPass);
            }

            $this->adminDAO->commit();
            
         
            require_once __DIR__ . '/../utils/MailService.php';
            \Utils\MailService::sendRepCredentialEmail(
                $data['email']  ?? '',
                $data['fname']  ?? '',
                $data['lname']  ?? '',
                $repId,
                $password
            );

            return [
                'success' => true,
                'message' => "Successfully assigned {$enrollmentNo} as Course Representative (Rep ID: {$repId}).",
                'rep_id' => $repId
            ];
        } catch (\Exception $e) {
            if ($this->adminDAO->inTransaction()) {
                $this->adminDAO->rollBack();
            }
            return ['success' => false, 'message' => 'Failed to assign rep: ' . $e->getMessage()];
        }
    }

    public function deactivateUser($targetUserId) {
        return $this->adminDAO->deactivateUser($targetUserId);
    }

    public function monitorPlatform() {
        $stats = $this->adminDAO->getPlatformStats();

        $content = [
            'lost_items' => [],
            'marketplace' => [],
            'notes' => []
        ];
        
        $itemsLost = $this->adminDAO->getPlatformLostItems();
        foreach ($itemsLost as $item) {
            $item['status'] = $item['status'] ?: 'active';
            $content['lost_items'][] = $item;
        }
        
        // Fetch Marketplace items
        $itemsMarket = $this->adminDAO->getPlatformMarketplace();
        foreach ($itemsMarket as $item) {
            $item['status'] = $item['status'] ?: 'active';
            $content['marketplace'][] = $item;
        }

        // Fetch Shared Notes
        $itemsNotes = $this->adminDAO->getPlatformNotes();
        foreach ($itemsNotes as $item) {
            $item['status'] = 'active'; // Notes doesn't have a status column yet
            $content['notes'][] = $item;
        }

        return [
            'stats' => $stats,
            'content' => $content,
            'reports' => []
        ];
    }

   
}
