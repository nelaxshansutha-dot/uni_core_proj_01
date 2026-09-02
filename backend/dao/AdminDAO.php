<?php
namespace DAO;

use PDO;

class AdminDAO extends BaseDAO {

    public function insertAdmin($adminID, $userID) {
        $query = "INSERT INTO admin (adminID, userID) VALUES (:adminID, :uid)";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':adminID', $adminID, PDO::PARAM_STR);
        $stmt->bindValue(':uid', $userID, PDO::PARAM_INT);
        return $stmt->execute();
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
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudentByUserId($userID) {
        $stmt = $this->db->prepare("SELECT enrollmentNo, courseID FROM student WHERE userID = :uid");
        $stmt->execute([':uid' => $userID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStudentCourse($courseID, $stdYear, $userID) {
        return $this->db->prepare("UPDATE student SET courseID = :cid, std_year = COALESCE(std_year, :yr) WHERE userID = :uid")
               ->execute([':cid' => $courseID, ':yr' => $stdYear, ':uid' => $userID]);
    }

    public function checkDuplicateRep($courseID, $userID, $batchYear) {
        $dupStmt = $this->db->prepare(
            "SELECT cr.userID, cr.enrollmentNo, u.fname, u.lname
             FROM course_representative cr
             JOIN users u ON cr.userID = u.userID
             WHERE cr.courseID = :cid
               AND cr.userID != :uid
               AND CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(cr.enrollmentNo, '/', 3), '/', -1) AS UNSIGNED) = :batch
             LIMIT 1"
        );
        $dupStmt->execute([':cid' => $courseID, ':uid' => $userID, ':batch' => $batchYear]);
        return $dupStmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRepByUserId($userID) {
        $existCheck = $this->db->prepare("SELECT repID FROM course_representative WHERE userID = :uid");
        $existCheck->execute([':uid' => $userID]);
        return $existCheck->fetch(PDO::FETCH_ASSOC);
    }

    public function updateRep($repId, $hashPass, $courseID, $userID) {
        return $this->db->prepare("UPDATE course_representative SET rep_id_string = :repid, hash_password = :hash, courseID = :cid WHERE userID = :uid")
               ->execute([':repid' => $repId, ':hash' => $hashPass, ':cid' => $courseID, ':uid' => $userID]);
    }

    public function insertRep($userID, $enrollmentNo, $courseID, $repId, $hashPass) {
        return $this->db->prepare(
            "INSERT INTO course_representative (userID, enrollmentNo, courseID, rep_id_string, hash_password) VALUES (:uid, :enr, :cid, :repid, :hash)"
        )->execute([
            ':uid' => $userID, 
            ':enr' => $enrollmentNo, 
            ':cid' => $courseID, 
            ':repid' => $repId, 
            ':hash' => $hashPass
        ]);
    }

    public function deactivateUser($targetUserId) {
        $query = "UPDATE users SET is_active = 0 WHERE userID = :uid";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':uid', $targetUserId);
        return $stmt->execute();
    }

    public function getPlatformStats() {
        return [
            'total_users' => (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'active_users' => (int)$this->db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
            'total_reps' => (int)$this->db->query("SELECT COUNT(*) FROM users WHERE role = 'course_representative'")->fetchColumn(),
            'total_posts' => (int)$this->db->query("SELECT (SELECT COUNT(*) FROM lost_items) + (SELECT COUNT(*) FROM notes) + (SELECT COUNT(*) FROM marketplace)")->fetchColumn(),
            'hidden_posts' => 0, 
            'recent_logs' => [] 
        ];
    }

    public function getPlatformLostItems() {
        $stmtLost = $this->db->query("SELECT l.lostID as lost_id, l.lostItemName, l.contact_number as contact_no, l.last_seen_datetime, l.item_image, u.email, l.created_at, l.status 
                            FROM lost_items l 
                            JOIN users u ON l.userID = u.userID 
                            ORDER BY l.created_at DESC LIMIT 50");
        return $stmtLost->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPlatformMarketplace() {
        $stmtMarket = $this->db->query("SELECT m.productID as id, m.productName as title, m.price, m.location, m.phone_number as contact_no, m.image_url as product_image, u.email, m.created_at, m.status 
                            FROM marketplace m 
                            JOIN users u ON m.userID = u.userID 
                            ORDER BY m.created_at DESC LIMIT 50");
        return $stmtMarket->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPlatformNotes() {
        $stmtNotes = $this->db->query("SELECT n.noteID as id, n.title, n.courseUnitID, n.file_url, u.email, n.created_at 
                            FROM Notes n 
                            JOIN student s ON n.enrollmentNo = s.enrollmentNo
                            JOIN users u ON s.userID = u.userID
                            ORDER BY n.created_at DESC LIMIT 50");
        return $stmtNotes->fetchAll(PDO::FETCH_ASSOC);
    }

    public function beginTransaction() {
        $this->db->beginTransaction();
    }

    public function commit() {
        $this->db->commit();
    }

    public function rollBack() {
        $this->db->rollBack();
    }
    
    public function inTransaction() {
        return $this->db->inTransaction();
    }

    public function getAdminIDByUserId($userID) {
        $adminStmt = $this->db->prepare("SELECT adminID FROM admin WHERE userID = :uid LIMIT 1");
        $adminStmt->execute([':uid' => $userID]);
        $adminRow  = $adminStmt->fetch(PDO::FETCH_ASSOC);
        return $adminRow ? $adminRow['adminID'] : null;
    }
}
