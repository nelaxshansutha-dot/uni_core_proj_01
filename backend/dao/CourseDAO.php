<?php
namespace DAO;

use PDO;

class CourseDAO extends BaseDAO {
    public function getCourseUnits($courseID) {
        $stmt = $this->db->prepare("SELECT * FROM course_units WHERE courseID = :cid");
        $stmt->bindParam(':cid', $courseID);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
