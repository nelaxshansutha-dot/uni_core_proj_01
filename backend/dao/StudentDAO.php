<?php
namespace DAO;

use PDO;
use Exception;

class StudentDAO extends BaseDAO {

    public function insertStudent($enrollmentNo, $userID, $courseID, $stdYear) {
        $query = "INSERT INTO student (enrollmentNo, userID, courseID, std_year) VALUES (:enr, :uid, :cid, :year)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':enr', $enrollmentNo);
        $stmt->bindValue(':uid', $userID, PDO::PARAM_INT);
        
        if (empty($courseID)) {
            $stmt->bindValue(':cid', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':cid', $courseID, PDO::PARAM_INT);
        }
        
        if (empty($stdYear)) {
            $stmt->bindValue(':year', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':year', $stdYear, PDO::PARAM_INT);
        }
        
        return $stmt->execute();
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

    public function getEnrollmentNoByUserId($userID) {
        $studentStmt = $this->db->prepare("SELECT enrollmentNo FROM student WHERE userID = :uid LIMIT 1");
        $studentStmt->execute([':uid' => $userID]);
        $studentRow = $studentStmt->fetch(PDO::FETCH_ASSOC);
        return $studentRow ? $studentRow['enrollmentNo'] : null;
    }

    public function searchStudents($queryStr) {
        $sql = "SELECT u.userID as id, u.fname as first_name, u.lname as last_name, u.email, u.phoneNum as phone_number, u.role, s.enrollmentNo as enrollment_no, s.courseID as course, s.std_year as year
                FROM users u 
                JOIN student s ON u.userID = s.userID 
                WHERE u.role = 'student' AND (s.enrollmentNo LIKE :q OR u.fname LIKE :q OR u.email LIKE :q)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':q' => "%$queryStr%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudentByUserId($userID) {
        $stmt = $this->db->prepare("SELECT courseID, enrollmentNo FROM student WHERE userID = :uid");
        $stmt->execute([':uid' => $userID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCourseId($userID, $courseID) {
        $stmt = $this->db->prepare("UPDATE student SET courseID = :cid WHERE userID = :uid");
        return $stmt->execute([':cid' => $courseID, ':uid' => $userID]);
    }

    public function updateYearIfNull($userID, $stdYear) {
        $stmt = $this->db->prepare("UPDATE student SET std_year = :yr WHERE userID = :uid AND (std_year IS NULL OR std_year = 0)");
        return $stmt->execute([':yr' => $stdYear, ':uid' => $userID]);
    }

    public function getStudentByEnrollmentNo($enrollmentNo) {
        $stmt = $this->db->prepare("SELECT courseID, std_year FROM student WHERE enrollmentNo = :enr LIMIT 1");
        $stmt->execute([':enr' => $enrollmentNo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getEnrollmentsForRepCourseWithNotification($repID) {
        $stmt = $this->db->prepare(
            "SELECT s.enrollmentNo 
             FROM student s
             JOIN users u ON s.userID = u.userID
             WHERE s.courseID = (SELECT courseID FROM course_representative WHERE repID = :rid LIMIT 1)
             AND u.peer_learning_app_notification = 1"
        );
        $stmt->execute([':rid' => $repID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
