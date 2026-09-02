<?php
namespace Models;

use DAO\PeerLearningRequestDAO;
use PDO;

class PeerLearningRequest {
    private $dao;

    private $requestID;
    private $courseUnitID;
    private $enrollmentNo;
    private $repID;
    private $stdYear;
    private $courseUnitName;
    private $semester;
    private $description;
    private $topic;
    private $status;
    private $created_at;

    public function __construct() {
        $this->dao = new PeerLearningRequestDAO();
    }

    public function hydrate(array $data = []): static {
        if (array_key_exists('requestID', $data)) {
            $this->setRequestID($data['requestID']);
        }
        if (array_key_exists('courseUnitID', $data)) {
            $this->setCourseUnitID($data['courseUnitID']);
        }
        if (array_key_exists('enrollmentNo', $data)) {
            $this->setEnrollmentNo($data['enrollmentNo']);
        } elseif (array_key_exists('enrolllmentNo', $data)) {
            $this->setEnrollmentNo($data['enrolllmentNo']);
        }
        if (array_key_exists('repID', $data)) {
            $this->setRepID($data['repID']);
        }
        if (array_key_exists('std_year', $data)) {
            $this->setStdYear($data['std_year']);
        } elseif (array_key_exists('stdYear', $data)) {
            $this->setStdYear($data['stdYear']);
        }
        if (array_key_exists('courseUnitName', $data)) {
            $this->setCourseUnitName($data['courseUnitName']);
        }
        if (array_key_exists('semester', $data)) {
            $this->setSemester($data['semester']);
        }
        if (array_key_exists('description', $data)) {
            $this->setDescription($data['description']);
        }
        if (array_key_exists('topic', $data)) {
            $this->setTopic($data['topic']);
        }
        if (array_key_exists('status', $data)) {
            $this->setStatus($data['status']);
        }
        if (array_key_exists('created_at', $data)) {
            $this->setCreatedAt($data['created_at']);
        }
        return $this;
    }

    public function hydrateFromRequest(array $data = []): static {
        if (array_key_exists('courseUnitID', $data)) {
            $this->setCourseUnitID($data['courseUnitID']);
        }
        if (array_key_exists('std_year', $data)) {
            $this->setStdYear($data['std_year']);
        } elseif (array_key_exists('stdYear', $data)) {
            $this->setStdYear($data['stdYear']);
        }
        if (array_key_exists('courseUnitName', $data)) {
            $this->setCourseUnitName($data['courseUnitName']);
        }
        if (array_key_exists('semester', $data)) {
            $this->setSemester($data['semester']);
        }
        if (array_key_exists('description', $data)) {
            $this->setDescription($data['description']);
        }
        return $this;
    }

    public function getRequestID() { return $this->requestID; }
    public function setRequestID($val) { $this->requestID = $val; return $this; }

    public function getCourseUnitID() { return $this->courseUnitID; }
    public function setCourseUnitID($val) { $this->courseUnitID = $val; return $this; }

    public function getRepID() { return $this->repID; }
    public function setRepID($val) { $this->repID = $val; return $this; }

    public function getEnrollmentNo() { return $this->enrollmentNo; }
    public function setEnrollmentNo($val) { $this->enrollmentNo = $val; return $this; }

    public function getStdYear() { return $this->stdYear; }
    public function setStdYear($val) { $this->stdYear = $val; return $this; }

    public function getCourseUnitName() { return $this->courseUnitName; }
    public function setCourseUnitName($val) { $this->courseUnitName = $val; return $this; }

    public function getSemester() { return $this->semester; }
    public function setSemester($val) { $this->semester = $val; return $this; }

    public function getDescription() { return $this->description; }
    public function setDescription($val) { $this->description = $val; return $this; }

    public function getTopic() { return $this->topic; }
    public function setTopic($val) { $this->topic = $val; return $this; }

    public function getStatus() { return $this->status; }
    public function setStatus($val) { $this->status = $val; return $this; }

    public function getCreatedAt() { return $this->created_at; }
    public function setCreatedAt($val) { $this->created_at = $val; return $this; }

    public function submit() {
        return $this->dao->create(
            $this->courseUnitID,
            $this->enrollmentNo,
            $this->repID,
            $this->stdYear,
            $this->courseUnitName,
            $this->semester,
            $this->description
        );
    }

    public function view($requestID = null) {
        return $this->dao->view($requestID);
    }

    public function review($requestID, $status) {
        return $this->dao->review($requestID, $status);
    }

    public function generateForm() {
        
        return "Form generated.";
    }
}
