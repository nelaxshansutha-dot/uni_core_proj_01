<?php

namespace Models;

use PDO;

class CourseRepresentative extends Student {
    
    protected $repID;
    protected $rep_id_string;
    protected $is_first_login;
  

    public function getRepID() { return $this->repID; }
    public function setRepID($val) { $this->repID = $val; return $this; }

    public function getRepIdString() { return $this->rep_id_string; }
    public function setRepIdString($val) { $this->rep_id_string = $val; return $this; }

    public function getIsFirstLogin() { return $this->is_first_login; }
    public function setIsFirstLogin($val) { $this->is_first_login = $val; return $this; }

    public function hydrate(array $data) {
        parent::hydrate($data);
        $this->repID = $data['repID'] ?? $this->repID;
        $this->rep_id_string = $data['rep_id_string'] ?? $this->rep_id_string;
        $this->is_first_login = $data['is_first_login'] ?? $this->is_first_login;
        // if course_representative hash_password exists, maybe set it to a specific property if needed
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
                throw new \Exception("Failed to register student part of rep");
            }
            $query = "INSERT INTO course_representative (userID, enrollmentNo, courseID, rep_id_string) 
                      VALUES (:uid, :enr, :cid, :repStr)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':uid', $this->userID);
            $stmt->bindParam(':enr', $this->enrollmentNo);
            if (empty($this->courseID)) {
                $stmt->bindValue(':cid', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':cid', $this->courseID, PDO::PARAM_INT);
            }
            $stmt->bindParam(':repStr', $this->rep_id_string);
            $stmt->execute();
            $this->repID = $this->conn->lastInsertId();
            
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

 
    public function updateNotes() {}
    public function deleteNotes() {}
    public function reviewPeerLearningRequest() {}
    public function sendNotification() {}
}
