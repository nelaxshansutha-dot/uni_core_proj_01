<?php
namespace DAO;

use PDO;

class PeerLearningRequestDAO extends BaseDAO {

    public function create($courseUnitID, $enrollmentNo, $repID, $stdYear, $courseUnitName, $semester, $description) {
        $query = "INSERT INTO peer_learning_request (courseUnitID, enrollmentNo, repID, std_year, courseUnitName, semester, description) 
                  VALUES (:cuid, :enr, :repid, :year, :name, :sem, :description)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':cuid' => $courseUnitID,
            ':enr' => $enrollmentNo,
            ':repid' => $repID,
            ':year' => $stdYear,
            ':name' => $courseUnitName,
            ':sem' => $semester,
            ':description' => $description
        ]);
    }

    public function view($requestID = null) {
        if ($requestID) {
            $stmt = $this->db->prepare("SELECT * FROM peer_learning_request WHERE requestID = :id");
            $stmt->execute([':id' => $requestID]);
            return $stmt->fetch();
        }
        return false;
    }

    public function review($requestID, $status) {
        $stmt = $this->db->prepare("UPDATE peer_learning_request SET status = :status WHERE requestID = :id");
        return $stmt->execute([':status' => $status, ':id' => $requestID]);
    }

    public function getGroupedRequestsForRep($repID) {
        $stmt = $this->db->prepare(
            "SELECT 
                courseUnitID,
                courseUnitName,
                MAX(semester) as semester,
                MAX(std_year) as std_year,
                CASE WHEN SUM(status = 'pending') > 0 THEN 'pending' ELSE MAX(status) END AS status,
                COUNT(DISTINCT enrollmentNo) AS request_count,
                GROUP_CONCAT(description ORDER BY created_at ASC SEPARATOR '|||') AS descriptions,
                MAX(created_at) AS latest_request
             FROM peer_learning_request
             WHERE repID = :rid
             GROUP BY courseUnitID, courseUnitName
             ORDER BY request_count DESC, latest_request DESC"
        );
        $stmt->execute([':rid' => $repID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRequestsByEnrollmentNo($enrollmentNo) {
        $stmt = $this->db->prepare(
            "SELECT plr.*, cu.courseUnitName as unitLabel
             FROM peer_learning_request plr
             LEFT JOIN course_units cu ON plr.courseUnitID = cu.courseUnitID
             WHERE plr.enrollmentNo = :enr
             ORDER BY plr.created_at DESC"
        );
        $stmt->execute([':enr' => $enrollmentNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasPendingRequest($enrollmentNo, $courseUnitID) {
        $dupCheck = $this->db->prepare(
            "SELECT requestID FROM peer_learning_request 
             WHERE enrollmentNo = :enr AND courseUnitID = :cuid AND status = 'pending'"
        );
        $dupCheck->execute([':enr' => $enrollmentNo, ':cuid' => $courseUnitID]);
        return (bool)$dupCheck->fetch();
    }

    public function updatePendingStatusForRepAndCourse($repID, $courseUnitID, $status) {
        $stmt = $this->db->prepare(
            "UPDATE peer_learning_request 
             SET status = :status 
             WHERE repID = :rid AND courseUnitID = :cuid AND status = 'pending'"
        );
        return $stmt->execute([':status' => $status, ':rid' => $repID, ':cuid' => $courseUnitID]);
    }

    public function getStudentsToNotifyForApprovedRequest($repID, $courseUnitID) {
        $students = $this->db->prepare(
            "SELECT plr.enrollmentNo 
             FROM peer_learning_request plr
             JOIN student s ON plr.enrollmentNo = s.enrollmentNo
             JOIN users u ON s.userID = u.userID
             WHERE plr.repID = :rid AND plr.courseUnitID = :cuid AND plr.status = 'approved'
             AND u.peer_learning_app_notification = 1"
        );
        $students->execute([':rid' => $repID, ':cuid' => $courseUnitID]);
        return $students->fetchAll(PDO::FETCH_ASSOC);
    }
}
