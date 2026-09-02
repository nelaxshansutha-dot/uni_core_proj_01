<?php
namespace DAO;

use PDO;

class CourseRepresentativeDAO extends BaseDAO {

    public function insertRep($userID, $enrollmentNo, $courseID, $repIdString) {
        $query = "INSERT INTO course_representative (userID, enrollmentNo, courseID, rep_id_string) 
                  VALUES (:uid, :enr, :cid, :repStr)";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':uid', $userID, PDO::PARAM_INT);
        $stmt->bindParam(':enr', $enrollmentNo);
        if (empty($courseID)) {
            $stmt->bindValue(':cid', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':cid', $courseID, PDO::PARAM_INT);
        }
        $stmt->bindParam(':repStr', $repIdString);
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    public function getCourseUnitName($courseUnitID) {
        $stmtUnit = $this->db->prepare("SELECT courseUnitName FROM course_units WHERE courseUnitID = :cuid");
        $stmtUnit->execute([':cuid' => $courseUnitID]);
        return $stmtUnit->fetchColumn();
    }

    public function getStudentEnrollmentsForCourse($courseID) {
        $stmtStud = $this->db->prepare("SELECT enrollmentNo FROM student WHERE courseID = :cid");
        $stmtStud->execute([':cid' => $courseID]);
        return $stmtStud->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePeerLearningRequestStatusCompleted($courseUnitID, $repID) {
        $stmtComp = $this->db->prepare("UPDATE peer_learning_request SET status = 'completed' WHERE courseUnitID = :cuid AND repID = :repid");
        return $stmtComp->execute([':cuid' => $courseUnitID, ':repid' => $repID]);
    }

    public function insertNotifications(array $studentIDs, $repID, $message) {
        if (empty($studentIDs)) return 0;

        $query = "INSERT INTO app_notification (repID, enrollmentNo, message) VALUES ";
        $values = [];
        $params = [];
        $i = 0;

        foreach ($studentIDs as $enr) {
            $values[] = "(:repid{$i}, :enr{$i}, :msg{$i})";
            $params[":repid{$i}"] = $repID;
            $params[":enr{$i}"] = $enr;
            $params[":msg{$i}"] = $message;
            $i++;
        }

        $query .= implode(', ', $values);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->rowCount();
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

    public function getIsFirstLogin($userID) {
        $repStmt = $this->db->prepare("SELECT is_first_login FROM course_representative WHERE userID = :uid LIMIT 1");
        $repStmt->execute([':uid' => $userID]);
        $repRow = $repStmt->fetch(PDO::FETCH_ASSOC);
        return $repRow ? (bool)$repRow['is_first_login'] : false;
    }

    public function forceChangePassword($userID, $hash) {
        $this->db->beginTransaction();
        try {
            $stmt1 = $this->db->prepare("UPDATE course_representative SET hash_password = :hash, is_first_login = 0 WHERE userID = :uid");
            $stmt1->execute([':hash' => $hash, ':uid' => $userID]);
            
            $stmt2 = $this->db->prepare("UPDATE users SET hash_password = :hash WHERE userID = :uid");
            $stmt2->execute([':hash' => $hash, ':uid' => $userID]);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteRepByUserId($userID) {
        $stmt = $this->db->prepare("DELETE FROM course_representative WHERE userID = ?");
        return $stmt->execute([$userID]);
    }

    public function getEnrollmentNoByUserId($userID) {
        $stmt = $this->db->prepare("SELECT enrollmentNo FROM course_representative WHERE userID = :uid LIMIT 1");
        $stmt->execute([':uid' => $userID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['enrollmentNo'] : null;
    }

    public function getRepIdStringByUserId($userID) {
        $repStmt = $this->db->prepare("SELECT rep_id_string FROM course_representative WHERE userID = :uid LIMIT 1");
        $repStmt->execute([':uid' => $userID]);
        $repRow = $repStmt->fetch(PDO::FETCH_ASSOC);
        return $repRow ? $repRow['rep_id_string'] : null;
    }

    public function getActiveRepsByCourseID($courseID) {
        $stmt = $this->db->prepare("SELECT repID, enrollmentNo FROM course_representative WHERE courseID = :cid AND (is_active = 1 OR is_active IS NULL)");
        $stmt->execute([':cid' => $courseID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRepIdByUserId($userID) {
        $stmt = $this->db->prepare("SELECT repID FROM course_representative WHERE userID = :uid LIMIT 1");
        $stmt->execute([':uid' => $userID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['repID'] : null;
    }

    public function getRepIdStringByRepId($repID) {
        $stmt = $this->db->prepare("SELECT rep_id_string FROM course_representative WHERE repID = :rid LIMIT 1");
        $stmt->execute([':rid' => $repID]);
        return $stmt->fetchColumn();
    }
}
