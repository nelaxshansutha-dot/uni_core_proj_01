<?php
namespace DAO;

use PDO;

class NotesDAO extends BaseDAO {

    public function fetchEnrollmentNo($userID) {
        $stmt = $this->db->prepare("SELECT enrollmentNo FROM student WHERE userID = :uid UNION SELECT enrollmentNo FROM course_representative WHERE userID = :uid LIMIT 1");
        $stmt->execute([':uid' => $userID]);
        return $stmt->fetch();
    }

    public function fetchCourseIDByUnit($courseUnitID) {
        $stmt = $this->db->prepare("SELECT courseID FROM course_units WHERE courseUnitID = :cuid");
        $stmt->execute([':cuid' => $courseUnitID]);
        return $stmt->fetch();
    }

    public function fetchCourseIDByEnrollment($enrollmentNo) {
        $stmt = $this->db->prepare("SELECT courseID FROM student WHERE enrollmentNo = :enr");
        $stmt->execute([':enr' => $enrollmentNo]);
        return $stmt->fetch();
    }

    public function fetchAllCourseUnits() {
        $stmt = $this->db->prepare("SELECT courseUnitID FROM course_units");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($enrollmentNo, $courseID, $courseUnitID, $title, $fileUrl, $description, $academicYear, $noteType) {
        $query = "INSERT INTO notes (enrollmentNo, courseID, courseUnitID, title, file_url, description, academicYear, noteType) 
                  VALUES (:enr, :cid, :cuid, :title, :file, :desc, :ayear, :ntype)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':enr' => $enrollmentNo,
            ':cid' => $courseID,
            ':cuid' => $courseUnitID,
            ':title' => $title,
            ':file' => $fileUrl,
            ':desc' => $description ?? null,
            ':ayear' => $academicYear ?? null,
            ':ntype' => $noteType ?? 'notes'
        ]);
        return $this->db->lastInsertId();
    }

    public function view($noteID = null, $filters = []) {
        if ($noteID) {
            $stmt = $this->db->prepare("SELECT n.*, cu.courseUnitName AS courseUniName, cu.academicYear, cu.semester FROM notes n LEFT JOIN course_units cu ON n.courseUnitID = cu.courseUnitID WHERE n.noteID = :nid");
            $stmt->execute([':nid' => $noteID]);
            return $stmt->fetch();
        } else {
            $query = "SELECT n.*, cu.courseUnitName AS courseUniName, cu.academicYear, cu.semester FROM notes n LEFT JOIN course_units cu ON n.courseUnitID = cu.courseUnitID WHERE 1=1";
            $params = [];
            
            if (!empty($filters['courseUnitID'])) {
                $query .= " AND n.courseUnitID = :cuid";
                $params[':cuid'] = $filters['courseUnitID'];
            }
            
            if (!empty($filters['academicYear'])) {
                $query .= " AND n.academicYear = :ayear";
                $params[':ayear'] = $filters['academicYear'];
            }
            
            if (!empty($filters['semester'])) {
                $query .= " AND cu.semester = :sem";
                $params[':sem'] = $filters['semester'];
            }
            
            if (!empty($filters['courseCode'])) {
                $query .= " AND LOWER(n.enrollmentNo) LIKE :cCode";
                $params[':cCode'] = "%/" . strtolower($filters['courseCode']) . "/%";
            }
            
            $query .= " ORDER BY n.academicYear DESC, n.created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }
    }

    public function update($noteID, $title, $description) {
        $query = "UPDATE notes SET title = :title, description = :desc WHERE noteID = :nid";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':title' => $title,
            ':desc' => $description,
            ':nid' => $noteID
        ]);
    }

    public function delete($noteID) {
        $stmt = $this->db->prepare("DELETE FROM notes WHERE noteID = :nid");
        return $stmt->execute([':nid' => $noteID]);
    }

    public function search($queryStr) {
        $q = "%" . $queryStr . "%";
        $stmt = $this->db->prepare("SELECT * FROM notes WHERE title LIKE :q OR description LIKE :q");
        $stmt->execute([':q' => $q]);
        return $stmt->fetchAll();
    }

    public function deleteByAdmin($noteID) {
        $stmt = $this->db->prepare("DELETE FROM notes WHERE noteID = ?");
        return $stmt->execute([$noteID]);
    }

    public function getNoteWithOwner($noteID) {
        $stmt = $this->db->prepare("SELECT u.email, n.title FROM notes n JOIN student s ON n.enrollmentNo = s.enrollmentNo JOIN users u ON s.userID = u.userID WHERE n.noteID = ?");
        $stmt->execute([$noteID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRecent($limit) {
        $stmt = $this->db->prepare("SELECT title, courseUnitID, created_at FROM notes ORDER BY created_at DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
