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

   
    public function getNoteID() 
    { return $this->noteID; }
    public function setNoteID($val) 
    { $this->noteID = $val; }

    public function getEnrollmentNo() 
    { return $this->enrollmentNo; }
    public function setEnrollmentNo($val) 
    { $this->enrollmentNo = $val; }

    public function getCourseID() 
    { return $this->courseID; }
    public function setCourseID($val) 
    { $this->courseID = $val; }

    public function getCourseUnitID() 
    { return $this->courseUnitID; }
    public function setCourseUnitID($val) 
    { $this->courseUnitID = $val; }

    public function getTitle() 
    { return $this->title; }
    public function setTitle($val) 
    { $this->title = $val; }

    public function getFileUrl() 
    { return $this->file_url; }
    public function setFileUrl($val) 
    { $this->file_url = $val; }

    public function getDescription() 
    { return $this->description; }
    public function setDescription($val) 
    { $this->description = $val; }

    public function getStatus() 
    { return $this->status; }
    public function setStatus($val) 
    { $this->status = $val; }

    public function getCreatedAt() 
    { return $this->created_at; }
    public function setCreatedAt($val) 
    { $this->created_at = $val; }

    public function upload($data) {
        if (empty($data['enrollmentNo']) && !empty($data['userID'])) {
            $stmt = $this->conn->prepare("SELECT enrollmentNo FROM student WHERE userID = :uid UNION SELECT enrollmentNo FROM course_representative WHERE userID = :uid LIMIT 1");
            $stmt->execute([':uid' => $data['userID']]);
            $res = $stmt->fetch();
            if ($res && !empty($res['enrollmentNo'])) {
                $data['enrollmentNo'] = $res['enrollmentNo'];
            }
        }
        $courseID = $data['courseID'] ?? null;
        if (!$courseID && !empty($data['courseUnitID'])) {
            $stmt = $this->conn->prepare("SELECT courseID FROM course_units WHERE courseUnitID = :cuid");
            $stmt->execute([':cuid' => $data['courseUnitID']]);
            $res = $stmt->fetch();
            if ($res) $courseID = $res['courseID'];
        }
        
     
        if (!$courseID && !empty($data['enrollmentNo'])) {
            $stmt = $this->conn->prepare("SELECT courseID FROM student WHERE enrollmentNo = :enr");
            $stmt->execute([':enr' => $data['enrollmentNo']]);
            $res = $stmt->fetch();
            if ($res) $courseID = $res['courseID'];
        }

       
        if (!$courseID && !empty($data['enrollmentNo'])) {
            $enrParts = explode('/', strtoupper(trim($data['enrollmentNo'])));
            $courseCode = $enrParts[1] ?? '';
            if ($courseCode === 'CST') $courseID = 1;
        }
        
        $providedCuid = $data['courseUnitID'] ?? '';
        $normalizedInput = strtoupper(str_replace([' ', '-'], '', $providedCuid));
        
        $stmtUnit = $this->conn->prepare("SELECT courseUnitID FROM course_units");
        $stmtUnit->execute();
        $allUnits = $stmtUnit->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($allUnits as $unit) {
            $dbUnit = strtoupper(str_replace([' ', '-'], '', $unit['courseUnitID']));
            if ($dbUnit === $normalizedInput || strpos($dbUnit, $normalizedInput) === 0) {
                $data['courseUnitID'] = $unit['courseUnitID'];
                break;
            }
        }
        
        try {
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
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'foreign key constraint fails') !== false && strpos($msg, 'courseUnitID') !== false) {
                throw new \Exception("The Course Code you entered does not exist in the system. Please verify the code.");
            }
            file_put_contents(__DIR__ . '/../error_log.txt', date('[Y-m-d H:i:s] ') . "Notes Upload DB Error: " . $msg . "\n", FILE_APPEND);
            throw $e;
        }
    }

    public function view($noteID = null, $filters = []) {
        if ($noteID) {
            $stmt = $this->conn->prepare("SELECT n.*, cu.courseUnitName AS courseUniName FROM notes n LEFT JOIN course_units cu ON n.courseUnitID = cu.courseUnitID WHERE n.noteID = :nid");
            $stmt->execute([':nid' => $noteID]);
            return $stmt->fetch();
        } else {
            $query = "SELECT n.*, cu.courseUnitName AS courseUniName FROM notes n LEFT JOIN course_units cu ON n.courseUnitID = cu.courseUnitID WHERE 1=1";
            $params = [];
            
        
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
            
            if (!empty($filters['academicYear'])) {
                $query .= " AND n.academicYear = :ayear";
                $params[':ayear'] = $filters['academicYear'];
            }
            
            if (!empty($filters['semester'])) {
                // If notes don't explicitly store semester, we might need to join course_units
                $query .= " AND cu.semester = :sem";
                $params[':sem'] = $filters['semester'];
            }
            
            if (!empty($filters['courseCode'])) {
                // n.enrollmentNo looks like UWU/CST/...
                $query .= " AND LOWER(n.enrollmentNo) LIKE :cCode";
                $params[':cCode'] = "%/" . strtolower($filters['courseCode']) . "/%";
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
