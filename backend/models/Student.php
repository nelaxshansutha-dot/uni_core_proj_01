<?php

namespace Models;

use PDO;

class Student extends User {
    
    protected $enrollmentNo;
    protected $courseID;
    protected $std_year;

    public function getEnrollmentNo() { return $this->enrollmentNo; }
    public function setEnrollmentNo($val) { $this->enrollmentNo = $val; return $this; }

    public function getCourseID() { return $this->courseID; }
    public function setCourseID($val) { $this->courseID = $val; return $this; }

    public function getStdYear() { return $this->std_year; }
    public function setStdYear($val) { $this->std_year = $val; return $this; }

    public function hydrate(array $data) {
        parent::hydrate($data);
        $this->enrollmentNo = $data['enrollmentNo'] ?? $this->enrollmentNo;
        $this->courseID = $data['courseID'] ?? $this->courseID;
        $this->std_year = $data['std_year'] ?? $this->std_year;
        return $this;
    }

    public function register() {
        $ownsTransaction = false;
        if (!$this->conn->inTransaction()) {
            $this->conn->beginTransaction();
            $ownsTransaction = true;
        }
        try {
            if (!parent::register()) {
                throw new \Exception("Failed to register user");
            }
            $query = "INSERT INTO student (enrollmentNo, userID, courseID, std_year) VALUES (:enr, :uid, :cid, :year)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':enr', $this->enrollmentNo);
            $stmt->bindParam(':uid', $this->userID, PDO::PARAM_INT);
            
            if (empty($this->courseID)) {
                $stmt->bindValue(':cid', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':cid', $this->courseID, PDO::PARAM_INT);
            }
            
            if (empty($this->std_year)) {
                $stmt->bindValue(':year', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':year', $this->std_year, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            if ($ownsTransaction) {
                $this->conn->commit();
            }
            return $this->userID;
        } catch (\Exception $e) {
            if ($ownsTransaction) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    // Standard Student Methods
    public function postLostItem() {}
    public function updateLostItem() {}
    public function deleteLostItem() {}
    public function viewLostItem() {}
    
    public function postMarketItem() {}
    public function updateMarketItem() {}
    public function deleteMarketItem() {}
    public function viewMarketItem() {}
    
    public function uploadNotes() {}
    public function viewNotes() {}
    public function downloadNotes() {}
    
    public function requestPeerLearningSession() {}
}
