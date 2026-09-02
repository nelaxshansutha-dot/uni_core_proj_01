<?php
namespace Models;

use DAO\NotesDAO;
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
    private $dao;

    private $userID;
    private $academicYear;
    private $noteType;

    public function __construct() {
        $this->dao = new NotesDAO();
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
            $res = $this->dao->fetchEnrollmentNo($this->userID);
            if ($res && !empty($res['enrollmentNo'])) {
                $this->enrollmentNo = $res['enrollmentNo'];
            }
        }
        $courseID = $this->courseID ?? null;
        if (!$courseID && !empty($this->courseUnitID)) {
            $res = $this->dao->fetchCourseIDByUnit($this->courseUnitID);
            if ($res) $courseID = $res['courseID'];
        }
        
        if (!$courseID && !empty($this->enrollmentNo)) {
            $res = $this->dao->fetchCourseIDByEnrollment($this->enrollmentNo);
            if ($res) $courseID = $res['courseID'];
        }

        if (!$courseID && !empty($this->enrollmentNo)) {
            $enrParts = explode('/', strtoupper(trim($this->enrollmentNo)));
            $courseCode = $enrParts[1] ?? '';
            if ($courseCode === 'CST') $courseID = 1;
        }
        
        $providedCuid = $this->courseUnitID ?? '';
        $normalizedInput = strtoupper(str_replace([' ', '-'], '', $providedCuid));
        
        $allUnits = $this->dao->fetchAllCourseUnits();
        
        foreach ($allUnits as $unit) {
            $dbUnit = strtoupper(str_replace([' ', '-'], '', $unit['courseUnitID']));
            if ($dbUnit === $normalizedInput || strpos($dbUnit, $normalizedInput) === 0) {
                $this->courseUnitID = $unit['courseUnitID'];
                break;
            }
        }
        
        try {
            $this->noteID = $this->dao->create(
                $this->enrollmentNo,
                $courseID,
                $this->courseUnitID,
                $this->title,
                $this->file_url,
                $this->description,
                $this->academicYear,
                $this->noteType
            );
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
        return $this->dao->view($noteID, $filters);
    }

    public function download($noteID) {
        $note = $this->view($noteID);
        return $note ? $note['file_url'] : null;
    }

    public function update() {
        return $this->dao->update($this->noteID, $this->title, $this->description);
    }

    public function delete($noteID) {
        return $this->dao->delete($noteID);
    }

    public function search($queryStr) {
        return $this->dao->search($queryStr);
    }
}
