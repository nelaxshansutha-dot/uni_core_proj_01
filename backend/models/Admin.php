<?php

namespace Models;

use PDO;
use Exception;

class Admin extends User {
    
    public function __construct(array $data = []) {
        parent::__construct($data);
    }

    public function register() {
        $this->conn->beginTransaction();
        try {
            if (!parent::register()) {
                throw new \Exception("Failed to register user");
            }
            $query = "INSERT INTO admin (userID) VALUES (:uid)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':uid', $this->getUserID(), PDO::PARAM_INT);
            $stmt->execute();
            $this->conn->commit();
            return $this->getUserID();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function manageUsers($role = 'all', $q = '') {
        if ($role === 'rep') $role = 'course_representative';
        
        $sql = "SELECT 
                    u.userID as id, 
                    u.fname as first_name, 
                    u.lname as last_name, 
                    u.email, 
                    u.role, 
                    u.is_active,
                    s.enrollmentNo as enrollment_no,
                    st.staffID as staff_id
                FROM users u
                LEFT JOIN student s ON u.userID = s.userID
                LEFT JOIN staff st ON u.userID = st.userID
                WHERE 1=1";
        $params = [];
        
        if ($role !== 'all') {
            $sql .= " AND u.role = :role";
            $params[':role'] = $role;
        }
        if (!empty($q)) {
            $sql .= " AND (u.fname LIKE :q OR u.lname LIKE :q OR u.email LIKE :q)";
            $params[':q'] = "%$q%";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as &$u) {
            if ($u['role'] === 'course_representative') {
                $u['role'] = 'rep';
            }
        }
        
        return $users;
    }

    public function assignCourseRep(array $data) {
        try {
            $this->conn->beginTransaction();

            $userID = $data['user_id'] ?? null;
            if (!$userID) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'User ID is required.'];
            }

         
            $stmt = $this->conn->prepare("SELECT enrollmentNo, courseID FROM student WHERE userID = :uid");
            $stmt->execute([':uid' => $userID]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            $enrollmentNo = $student ? $student['enrollmentNo'] : null;

            if (!$enrollmentNo) {
                $this->conn->rollBack();
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
                $this->conn->prepare("UPDATE student SET courseID = :cid, std_year = COALESCE(std_year, :yr) WHERE userID = :uid")
                   ->execute([':cid' => $courseID, ':yr' => $stdYear, ':uid' => $userID]);
            }

            $repId = !empty($data['rep_id']) 
                ? strtolower(trim($data['rep_id'])) 
                : 'rep_' . strtolower($enrollmentNo);

            $password = $data['password'] ?? '';
            $hashPass = password_hash($password, PASSWORD_BCRYPT);

        
            $existCheck = $this->conn->prepare("SELECT repID FROM course_representative WHERE userID = :uid");
            $existCheck->execute([':uid' => $userID]);
            $existRep = $existCheck->fetch(PDO::FETCH_ASSOC);

            if ($existRep) {
             
                $this->conn->prepare("UPDATE course_representative SET rep_id_string = :repid, hash_password = :hash, courseID = :cid WHERE userID = :uid")
                   ->execute([':repid' => $repId, ':hash' => $hashPass, ':cid' => $courseID, ':uid' => $userID]);
            } else {
              
                $this->conn->prepare(
                    "INSERT INTO course_representative (userID, enrollmentNo, courseID, rep_id_string, hash_password) VALUES (:uid, :enr, :cid, :repid, :hash)"
                )->execute([
                    ':uid' => $userID, 
                    ':enr' => $enrollmentNo, 
                    ':cid' => $courseID, 
                    ':repid' => $repId, 
                    ':hash' => $hashPass
                ]);
            }

            $this->conn->commit();
            
         
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
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'message' => 'Failed to assign rep: ' . $e->getMessage()];
        }
    }

    public function deactivateUser($targetUserId) {
        $query = "UPDATE users SET is_active = 0 WHERE userID = :uid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $targetUserId);
        return $stmt->execute();
    }

    public function monitorPlatform() {
        $stats = [
            'total_users' => (int)$this->conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'active_users' => (int)$this->conn->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
            'total_reps' => (int)$this->conn->query("SELECT COUNT(*) FROM users WHERE role = 'course_representative'")->fetchColumn(),
            'total_posts' => (int)$this->conn->query("SELECT (SELECT COUNT(*) FROM lost_items) + (SELECT COUNT(*) FROM notes) + (SELECT COUNT(*) FROM marketplace)")->fetchColumn(),
            'hidden_posts' => 0, // Mock for now
            'recent_logs' => [] // Mock empty array so frontend map doesn't crash
        ];

        $content = [
            'lost_items' => [],
            'marketplace' => [],
            'notes' => []
        ];
        
        // Fetch Lost Items
        $stmtLost = $this->conn->query("SELECT l.lostID as lost_id, l.lostItemName, l.contact_number as contact_no, l.last_seen_datetime, l.item_image, u.email, l.created_at, l.status 
                            FROM lost_items l 
                            JOIN users u ON l.userID = u.userID 
                            ORDER BY l.created_at DESC LIMIT 50");
        $itemsLost = $stmtLost->fetchAll(PDO::FETCH_ASSOC);
        foreach ($itemsLost as $item) {
            $item['status'] = $item['status'] ?: 'active';
            $content['lost_items'][] = $item;
        }
        
        // Fetch Marketplace items
        $stmtMarket = $this->conn->query("SELECT m.productID as id, m.productName as title, m.price, m.location, m.phone_number as contact_no, m.image_url as product_image, u.email, m.created_at, m.status 
                            FROM marketplace m 
                            JOIN users u ON m.userID = u.userID 
                            ORDER BY m.created_at DESC LIMIT 50");
        $itemsMarket = $stmtMarket->fetchAll(PDO::FETCH_ASSOC);
        foreach ($itemsMarket as $item) {
            $item['status'] = $item['status'] ?: 'active';
            $content['marketplace'][] = $item;
        }

        // Fetch Shared Notes
        $stmtNotes = $this->conn->query("SELECT n.noteID as id, n.title, n.courseUnitID, n.file_url, u.email, n.created_at 
                            FROM Notes n 
                            JOIN student s ON n.enrollmentNo = s.enrollmentNo
                            JOIN users u ON s.userID = u.userID
                            ORDER BY n.created_at DESC LIMIT 50");
        $itemsNotes = $stmtNotes->fetchAll(PDO::FETCH_ASSOC);
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

    public function viewSystemLogs() {
        // TODO: Implement a proper admin_logs / activity_logs table. 
        // For now, return an empty array to match previous mock behavior.
        return [];
    }
}
