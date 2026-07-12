<?php
namespace Models;
use Config\Database;
use PDO;

class Notes {
    private $noteID;
    private $enrollmentNo;
    private $courseID;
    private $courseUnitID;
    private $title;
    private $file_url;
    private $description;
    private $status;
    private $created_at;
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function upload($data) {
        $courseID = $data['courseID'] ?? null;
        if (!$courseID && !empty($data['courseUnitID'])) {
            $stmt = $this->conn->prepare("SELECT courseID FROM course_units WHERE courseUnitID = :cuid");
            $stmt->execute([':cuid' => $data['courseUnitID']]);
            $res = $stmt->fetch();
            if ($res) $courseID = $res['courseID'];
        }
        
        $query = "INSERT INTO notes (enrollmentNo, courseID, courseUnitID, title, file_url, description, academicYear, noteType) 
                  VALUES (:enr, :cid, :cuid, :title, :file, :desc, :ayear, :ntype)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':enr' => $data['enrollmentNo'],
            ':cid' => $courseID,
            ':cuid' => $data['courseUnitID'],
            ':title' => $data['title'],
            ':file' => $data['file_url'],
            ':desc' => $data['description'] ?? null,
            ':ayear' => $data['academicYear'] ?? null,
            ':ntype' => $data['noteType'] ?? 'notes'
        ]);
        return $this->conn->lastInsertId();
    }

    public function view($noteID = null, $filters = []) {
        if ($noteID) {
            $stmt = $this->conn->prepare("SELECT n.*, cu.courseUniName FROM notes n LEFT JOIN course_units cu ON n.courseUnitID = cu.courseUnitID WHERE n.noteID = :nid");
            $stmt->execute([':nid' => $noteID]);
            return $stmt->fetch();
        } else {
            $query = "SELECT n.*, cu.courseUniName FROM notes n LEFT JOIN course_units cu ON n.courseUnitID = cu.courseUnitID WHERE 1=1";
            $params = [];
            
            // Access control by user's course
            if (!empty($filters['enrollmentNo'])) {
                $parts = explode('/', strtolower($filters['enrollmentNo']));
                if (count($parts) >= 3) {
                    $courseCode = $parts[1]; // e.g. 'cst'
                    // We only want notes uploaded by students in the same course, OR we assume the note's enrollmentNo indicates the course it belongs to.
                    // Better yet, the system relies on checking the uploader's course.
                    $query .= " AND LOWER(n.enrollmentNo) LIKE :courseFilter";
                    $params[':courseFilter'] = "%/{$courseCode}/%";
                }
            }
            
            if (!empty($filters['courseUnitID'])) {
                $query .= " AND n.courseUnitID = :cuid";
                $params[':cuid'] = $filters['courseUnitID'];
            }
            
            $query .= " ORDER BY n.academicYear DESC, n.created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }
    }

    public function download($noteID) {
        // Logic for download tracking if needed, otherwise returns file_url
        $note = $this->view($noteID);
        return $note ? $note['file_url'] : null;
    }

    public function update($noteID, $data) {
        $query = "UPDATE notes SET title = :title, description = :desc WHERE noteID = :nid";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':title' => $data['title'],
            ':desc' => $data['description'],
            ':nid' => $noteID
        ]);
    }

    public function delete($noteID) {
        $stmt = $this->conn->prepare("DELETE FROM notes WHERE noteID = :nid");
        return $stmt->execute([':nid' => $noteID]);
    }

    public function search($queryStr) {
        $q = "%" . $queryStr . "%";
        $stmt = $this->conn->prepare("SELECT * FROM notes WHERE title LIKE :q OR description LIKE :q");
        $stmt->execute([':q' => $q]);
        return $stmt->fetchAll();
    }
}
