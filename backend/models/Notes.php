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

    private $userID;
    private $academicYear;
    private $noteType;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function hydrate(array $data = []): static {
        if (array_key_exists('noteID', $data)) {
            $this->setNoteID($data['noteID']);
        }
        if (array_key_exists('enrollmentNo', $data)) {
            $this->setEnrollmentNo($data['enrollmentNo']);
        }
        if (array_key_exists('courseID', $data)) {
            $this->setCourseID($data['courseID']);
        }
        if (array_key_exists('courseUnitID', $data)) {
            $this->setCourseUnitID($data['courseUnitID']);
        }
        if (array_key_exists('title', $data)) {
            $this->setTitle($data['title']);
        }
        if (array_key_exists('file_url', $data)) {
            $this->setFileUrl($data['file_url']);
        }
        if (array_key_exists('description', $data)) {
            $this->setDescription($data['description']);
        }
        if (array_key_exists('status', $data)) {
            $this->setStatus($data['status']);
        }
        if (array_key_exists('created_at', $data)) {
            $this->setCreatedAt($data['created_at']);
        }
        if (array_key_exists('userID', $data)) {
            $this->setUserID($data['userID']);
        }
        if (array_key_exists('academicYear', $data)) {
            $this->setAcademicYear($data['academicYear']);
        }
        if (array_key_exists('noteType', $data)) {
            $this->setNoteType($data['noteType']);
        }
        return $this;
    }

    public function hydrateFromRequest(array $data = []): static {
        if (array_key_exists('courseUnitID', $data)) {
            $this->setCourseUnitID($data['courseUnitID']);
        }
        if (array_key_exists('title', $data)) {
            $this->setTitle($data['title']);
        }
        if (array_key_exists('file_url', $data)) {
            $this->setFileUrl($data['file_url']);
        }
        if (array_key_exists('description', $data)) {
            $this->setDescription($data['description']);
        }
        if (array_key_exists('academicYear', $data)) {
            $this->setAcademicYear($data['academicYear']);
        }
        if (array_key_exists('noteType', $data)) {
            $this->setNoteType($data['noteType']);
        }
        return $this;
    }

   
    public function getNoteID() 
    { return $this->noteID; }
    public function setNoteID($val) 
    { $this->noteID = $val; return $this; }

    public function getEnrollmentNo() 
    { return $this->enrollmentNo; }
    public function setEnrollmentNo($val) 
    { $this->enrollmentNo = $val; return $this; }

    public function getCourseID() 
    { return $this->courseID; }
    public function setCourseID($val) 
    { $this->courseID = $val; return $this; }

    public function getCourseUnitID() 
    { return $this->courseUnitID; }
    public function setCourseUnitID($val) 
    { $this->courseUnitID = $val; return $this; }

    public function getTitle() 
    { return $this->title; }
    public function setTitle($val) 
    { $this->title = $val; return $this; }

    public function getFileUrl() 
    { return $this->file_url; }
    public function setFileUrl($val) 
    { $this->file_url = $val; return $this; }

    public function getDescription() 
    { return $this->description; }
    public function setDescription($val) 
    { $this->description = $val; return $this; }

    public function getStatus() 
    { return $this->status; }
    public function setStatus($val) 
    { $this->status = $val; return $this; }

    public function getCreatedAt() 
    { return $this->created_at; }
    public function setCreatedAt($val) 
    { $this->created_at = $val; return $this; }

    public function getUserID() { return $this->userID; }
    public function setUserID($val) { $this->userID = $val; return $this; }

    public function getAcademicYear() { return $this->academicYear; }
    public function setAcademicYear($val) { $this->academicYear = $val; return $this; }

    public function getNoteType() { return $this->noteType; }
    public function setNoteType($val) { $this->noteType = $val; return $this; }

    public function upload() {
        if (empty($this->enrollmentNo) && !empty($this->userID)) {
            $stmt = $this->conn->prepare("SELECT enrollmentNo FROM student WHERE userID = :uid UNION SELECT enrollmentNo FROM course_representative WHERE userID = :uid LIMIT 1");
            $stmt->execute([':uid' => $this->userID]);
            $res = $stmt->fetch();
            if ($res && !empty($res['enrollmentNo'])) {
                $this->enrollmentNo = $res['enrollmentNo'];
            }
        }
        $courseID = $this->courseID ?? null;
        if (!$courseID && !empty($this->courseUnitID)) {
            $stmt = $this->conn->prepare("SELECT courseID FROM course_units WHERE courseUnitID = :cuid");
            $stmt->execute([':cuid' => $this->courseUnitID]);
            $res = $stmt->fetch();
            if ($res) $courseID = $res['courseID'];
        }
        
     
        if (!$courseID && !empty($this->enrollmentNo)) {
            $stmt = $this->conn->prepare("SELECT courseID FROM student WHERE enrollmentNo = :enr");
            $stmt->execute([':enr' => $this->enrollmentNo]);
            $res = $stmt->fetch();
            if ($res) $courseID = $res['courseID'];
        }

       
        if (!$courseID && !empty($this->enrollmentNo)) {
            $enrParts = explode('/', strtoupper(trim($this->enrollmentNo)));
            $courseCode = $enrParts[1] ?? '';
            if ($courseCode === 'CST') $courseID = 1;
        }
        
        $providedCuid = $this->courseUnitID ?? '';
        $normalizedInput = strtoupper(str_replace([' ', '-'], '', $providedCuid));
        
        $stmtUnit = $this->conn->prepare("SELECT courseUnitID FROM course_units");
        $stmtUnit->execute();
        $allUnits = $stmtUnit->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($allUnits as $unit) {
            $dbUnit = strtoupper(str_replace([' ', '-'], '', $unit['courseUnitID']));
            if ($dbUnit === $normalizedInput || strpos($dbUnit, $normalizedInput) === 0) {
                $this->courseUnitID = $unit['courseUnitID'];
                break;
            }
        }
        
        try {
            $query = "INSERT INTO notes (enrollmentNo, courseID, courseUnitID, title, file_url, description, academicYear, noteType) 
                      VALUES (:enr, :cid, :cuid, :title, :file, :desc, :ayear, :ntype)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':enr' => $this->enrollmentNo,
                ':cid' => $courseID,
                ':cuid' => $this->courseUnitID,
                ':title' => $this->title,
                ':file' => $this->file_url,
                ':desc' => $this->description ?? null,
                ':ayear' => $this->academicYear ?? null,
                ':ntype' => $this->noteType ?? 'notes'
            ]);
            $this->noteID = $this->conn->lastInsertId();
            return $this->noteID;
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
            $stmt = $this->conn->prepare("SELECT n.*, cu.courseUnitName AS courseUniName, cu.academicYear, cu.semester FROM notes n LEFT JOIN course_units cu ON n.courseUnitID = cu.courseUnitID WHERE n.noteID = :nid");
            $stmt->execute([':nid' => $noteID]);
            return $stmt->fetch();
        } else {
            $query = "SELECT n.*, cu.courseUnitName AS courseUniName, cu.academicYear, cu.semester FROM notes n LEFT JOIN course_units cu ON n.courseUnitID = cu.courseUnitID WHERE 1=1";
            $params = [];
            
        
            // Note: we intentionally do NOT filter by the viewer's own enrollmentNo/course here.
            // Notes are shared across all courses. Use the explicit courseCode filter below if needed.
            
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

    public function update() {
        $query = "UPDATE notes SET title = :title, description = :desc WHERE noteID = :nid";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':title' => $this->title,
            ':desc' => $this->description,
            ':nid' => $this->noteID
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
