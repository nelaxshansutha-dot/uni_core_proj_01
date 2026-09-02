<?php
namespace DAO;

use PDO;

class CourseUnitDAO extends BaseDAO {
    public function create($courseUnitID, $courseID, $courseUniName, $semester) {
        $query = "INSERT INTO course_units (courseUnitID, courseID, courseUnitName, semester) 
                  VALUES (:cuid, :cid, :name, :sem)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':cuid' => $courseUnitID,
            ':cid' => $courseID,
            ':name' => $courseUniName,
            ':sem' => $semester
        ]);
    }

    public function view($courseUnitID = null) {
        if ($courseUnitID) {
            $stmt = $this->db->prepare("SELECT * FROM course_units WHERE courseUnitID = :cuid");
            $stmt->execute([':cuid' => $courseUnitID]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM course_units ORDER BY semester ASC, courseUnitName ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getRequest($courseUnitID) {
        $stmt = $this->db->prepare("SELECT * FROM peer_learning_request WHERE courseUnitID = :cuid");
        $stmt->bindValue(':cuid', $courseUnitID, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getModulesForCourse($courseID, $academicYear, $semester) {
        $stmt = $this->db->prepare("SELECT courseUnitID, courseUnitName FROM course_units WHERE courseID = :cid AND academicYear = :year AND semester = :sem");
        $stmt->execute([':cid' => $courseID, ':year' => $academicYear, ':sem' => $semester]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT courseUnitID, courseUnitName, academicYear FROM course_units");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
