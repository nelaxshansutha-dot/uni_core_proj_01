<?php
namespace DAO;

use PDO;

class AppNotificationDAO extends BaseDAO {
    public function getUserPreference($enrollmentNo) {
        $stmt = $this->db->prepare("SELECT peer_learning_app_notification FROM users u JOIN student s ON u.userID = s.userID WHERE s.enrollmentNo = :enr");
        $stmt->execute([':enr' => $enrollmentNo]);
        return $stmt->fetchColumn();
    }

    public function insert($repID, $enrollmentNo, $message) {
        $query = "INSERT INTO app_notification (repID, enrollmentNo, message) VALUES (:repid, :enr, :msg)";
        $ins = $this->db->prepare($query);
        return $ins->execute([
            ':repid' => $repID,
            ':enr' => $enrollmentNo,
            ':msg' => $message
        ]);
    }

    public function view($enrollmentNo) {
        $stmt = $this->db->prepare("SELECT * FROM app_notification WHERE enrollmentNo = :enr ORDER BY created_at DESC");
        $stmt->execute([':enr' => $enrollmentNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentForUser($userID, $limit) {
        $stmt = $this->db->prepare("SELECT message, created_at FROM app_notification an JOIN student s ON an.enrollmentNo = s.enrollmentNo WHERE s.userID = :uid ORDER BY an.created_at DESC LIMIT :lim");
        $stmt->bindValue(':uid', $userID, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNotificationsForUser($userID) {
        $stmt = $this->db->prepare(
            "SELECT an.appID AS id, an.message, an.created_at, 'peer_learning' AS type
             FROM app_notification an
             WHERE an.enrollmentNo IN (
                 SELECT s.enrollmentNo FROM student s WHERE s.userID = :uid1
                 UNION
                 SELECT cr.enrollmentNo FROM course_representative cr WHERE cr.userID = :uid2
             )
             ORDER BY an.created_at DESC
             LIMIT 20"
        );
        $stmt->execute([':uid1' => $userID, ':uid2' => $userID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteByAppIDAndEnrollment($appID, $enrollmentNo) {
        $stmt = $this->db->prepare("DELETE FROM app_notification WHERE appID = :id AND enrollmentNo = :enr");
        return $stmt->execute([':id' => $appID, ':enr' => $enrollmentNo]);
    }

    public function getAllByEnrollment($enrollmentNo) {
        $stmt = $this->db->prepare(
            "SELECT appID, message, created_at
             FROM app_notification
             WHERE enrollmentNo = :enr
             ORDER BY created_at DESC LIMIT 20"
        );
        $stmt->execute([':enr' => $enrollmentNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
