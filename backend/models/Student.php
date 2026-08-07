<?php

namespace Models;

use PDO;
use Exception;

class Student extends User {
    
    protected $enrollmentNo;
    protected $courseID;
    protected $std_year;

    public static function extractBatchYear(string $enrollmentNo): ?string {
        if (preg_match('/UWU\/[A-Z]+\/(\d{2})\//', strtoupper($enrollmentNo), $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function getEnrollmentNo() 
    { return $this->enrollmentNo; }

    public function setEnrollmentNo($val)
    { $this->enrollmentNo = $val; return $this; }

    public function getCourseID() 
    { return $this->courseID; }
    public function setCourseID($val)
    { $this->courseID = $val; return $this; }

    public function getStdYear() 
    { return $this->std_year; }
    public function setStdYear($val)
    { $this->std_year = $val; return $this; }

    public function __construct() {
        parent::__construct();
    }

    public function hydrate(array $data = []): static {
        parent::hydrate($data);
        if (array_key_exists('enrollmentNo', $data)) {
            $this->setEnrollmentNo($data['enrollmentNo']);
        } elseif (array_key_exists('enrollment_no', $data)) {
            $this->setEnrollmentNo($data['enrollment_no']);
        }

        if (array_key_exists('courseID', $data)) {
            $this->setCourseID($data['courseID']);
        } elseif (array_key_exists('course', $data)) {
            $this->setCourseID($data['course']);
        }

        if (array_key_exists('std_year', $data)) {
            $this->setStdYear($data['std_year']);
        } elseif (array_key_exists('year', $data)) {
            $this->setStdYear($data['year']);
        }

        if (!empty($this->enrollmentNo) && strpos(strtoupper($this->enrollmentNo), 'UWU/CST') !== false) {
            if (empty($this->courseID)) {
                $this->setCourseID(1);
            }
            if (empty($this->std_year)) {
                $this->setStdYear(1);
            }
        }
        return $this;
    }

    public function hydrateFromRequest(array $data = []): static {
        parent::hydrateFromRequest($data);
        if (array_key_exists('enrollmentNo', $data)) {
            $this->setEnrollmentNo($data['enrollmentNo']);
        } elseif (array_key_exists('enrollment_no', $data)) {
            $this->setEnrollmentNo($data['enrollment_no']);
        }

        if (array_key_exists('courseID', $data)) {
            $this->setCourseID($data['courseID']);
        } elseif (array_key_exists('course', $data)) {
            $this->setCourseID($data['course']);
        }

        if (array_key_exists('std_year', $data)) {
            $this->setStdYear($data['std_year']);
        } elseif (array_key_exists('year', $data)) {
            $this->setStdYear($data['year']);
        }

        if (!empty($this->enrollmentNo) && strpos(strtoupper($this->enrollmentNo), 'UWU/CST') !== false) {
            if (empty($this->courseID)) {
                $this->setCourseID(1);
            }
            if (empty($this->std_year)) {
                $this->setStdYear(1);
            }
        }
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
                throw new Exception("Failed to register user");
            }
            $query = "INSERT INTO student (enrollmentNo, userID, courseID, std_year) VALUES (:enr, :uid, :cid, :year)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':enr', $this->enrollmentNo);
            $stmt->bindValue(':uid', $this->getUserID(), PDO::PARAM_INT);
            
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
            return $this->getUserID();
        } catch (Exception $e) {
            if ($ownsTransaction) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }


    public function postLostItem(LostItem $lostItem) {
        try {
            $lostItem->setUserID($this->getUserID());
            return $lostItem->create();
        } catch (Exception $e) {
            throw new Exception("Failed to post lost item: " . $e->getMessage());
        }
    }

    public function updateLostItem(LostItem $lostItem): bool {
        try {
            $lostItem->setUserID($this->getUserID()); // Ownership check via SQL in LostItem model
            return $lostItem->update($lostItem->getLostID());
        } catch (Exception $e) {
            throw new Exception("Failed to update lost item: " . $e->getMessage());
        }
    }

    public function deleteLostItem($lostID): bool {
        try {
            $lostItem = new LostItem();
            // Ownership check via SQL in LostItem model
            return $lostItem->delete($lostID, $this->getUserID());
        } catch (Exception $e) {
            throw new Exception("Failed to delete lost item: " . $e->getMessage());
        }
    }

    public function viewLostItem($lostID = null) {
        try {
            $lostItem = new LostItem();
            return $lostItem->view($lostID);
        } catch (Exception $e) {
            throw new Exception("Failed to view lost item: " . $e->getMessage());
        }
    }
    
 
    public function postMarketItem(Marketplace $marketItem) {
        try {
            $marketItem->setUserID($this->getUserID());
            return $marketItem->create();
        } catch (Exception $e) {
            throw new Exception("Failed to post market item: " . $e->getMessage());
        }
    }

    public function updateMarketItem(Marketplace $marketItem): bool {
        try {
            $marketItem->setUserID($this->getUserID());
            // Ownership check via SQL in Marketplace model
            return $marketItem->update();
        } catch (Exception $e) {
            throw new Exception("Failed to update market item: " . $e->getMessage());
        }
    }

    public function deleteMarketItem($productID): bool {
        try {
            $marketItem = new Marketplace();
            // Ownership check via SQL in Marketplace model
            return $marketItem->delete($productID, $this->getUserID());
        } catch (Exception $e) {
            throw new Exception("Failed to delete market item: " . $e->getMessage());
        }
    }

    public function viewMarketItem($productID = null) {
        try {
            $marketItem = new Marketplace();
            return $marketItem->view($productID);
        } catch (Exception $e) {
            throw new Exception("Failed to view market item: " . $e->getMessage());
        }
    }
    
   
    public function uploadNotes(Notes $notes) {
        try {
            $notes->setEnrollmentNo($this->enrollmentNo);
            $notes->setUserID($this->getUserID());
            return $notes->upload();
        } catch (Exception $e) {
            throw new Exception("Failed to upload notes: " . $e->getMessage());
        }
    }

    public function updateNotes(Notes $notes): bool {
        try {
            $note = $notes->view($notes->getNoteID());
            if (!$note || strtolower($note['enrollmentNo']) !== strtolower($this->enrollmentNo)) {
                throw new Exception("Unauthorized: You do not own this note.");
            }
            return $notes->update();
        } catch (Exception $e) {
            throw new Exception("Failed to update notes: " . $e->getMessage());
        }
    }

    public function deleteNotes($noteID): bool {
        try {
            $notes = new Notes();
            
            $note = $notes->view($noteID);
            if (!$note || strtolower($note['enrollmentNo']) !== strtolower($this->enrollmentNo)) {
                throw new Exception("Unauthorized: You do not own this note.");
            }
            return $notes->delete($noteID);
        } catch (Exception $e) {
            throw new Exception("Failed to delete notes: " . $e->getMessage());
        }
    }

    public function viewNotes($noteID = null, $filters = []) {
        try {
            $notes = new Notes();
            return $notes->view($noteID, $filters);
        } catch (Exception $e) {
            throw new Exception("Failed to view notes: " . $e->getMessage());
        }
    }

    public function downloadNotes($noteID) {
        try {
            $notes = new Notes();
            $fileUrl = $notes->download($noteID);
            if (!$fileUrl) {
                throw new Exception("Note not found or no file available.");
            }
         
            return $fileUrl;
        } catch (Exception $e) {
            throw new Exception("Failed to download notes: " . $e->getMessage());
        }
    }
    

    public function requestPeerLearningSession(PeerLearningRequest $request) {
        try {
            $request->setEnrollmentNo($this->enrollmentNo);
            return $request->submit();
        } catch (Exception $e) {
            throw new Exception("Failed to request peer learning session: " . $e->getMessage());
        }
    }
}
