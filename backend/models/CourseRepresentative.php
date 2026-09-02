<?php

namespace Models;

use PDO;

class CourseRepresentative extends Student
{
    private $repID;
    private $rep_id_string;
    private $is_first_login;
    private $courseRepDAO;


    public function getRepID()
    {
        return $this->repID;
    }
    public function setRepID($val)
    {
        $this->repID = $val;
        return $this;
    }

    public function getRepIdString()
    {
        return $this->rep_id_string;
    }
    public function setRepIdString($val)
    {
        $this->rep_id_string = $val;
        return $this;
    }

    public function getIsFirstLogin()
    {
        return $this->is_first_login;
    }
    public function setIsFirstLogin($val)
    {
        $this->is_first_login = $val;
        return $this;
    }

    public function __construct()
    {
        parent::__construct();
        $this->courseRepDAO = new \DAO\CourseRepresentativeDAO();
    }

    public function hydrate(array $data = []): static
    {
        parent::hydrate($data);
        if (array_key_exists('repID', $data)) {
            $this->setRepID($data['repID']);
        }
        if (array_key_exists('rep_id_string', $data)) {
            $this->setRepIdString($data['rep_id_string']);
        } elseif (array_key_exists('rep_id', $data)) {
            $this->setRepIdString($data['rep_id']);
        }
        if (array_key_exists('is_first_login', $data)) {
            $this->setIsFirstLogin($data['is_first_login']);
        }
        return $this;
    }

    public function hydrateFromRequest(array $data = []): static
    {
        parent::hydrateFromRequest($data);
        return $this;
    }

    public function register()
    {
        $ownsTransaction = false;
        if (!$this->courseRepDAO->inTransaction()) {
            $this->courseRepDAO->beginTransaction();
            $ownsTransaction = true;
        }
        try {
            if (!parent::register()) {
                throw new \Exception("Failed to register student part of rep");
            }
            
            $this->repID = $this->courseRepDAO->insertRep(
                $this->getUserID(),
                $this->enrollmentNo,
                $this->courseID,
                $this->rep_id_string
            );

            if ($ownsTransaction) {
                $this->courseRepDAO->commit();
            }
            return $this->getUserID();
        } catch (\Exception $e) {
            if ($ownsTransaction) {
                $this->courseRepDAO->rollBack();
            }
            throw $e;
        }
    }

    
    public function updateNotes(Notes $notes): bool
    {
        $note = $notes->view($notes->getNoteID());

        if (!$note) {
            throw new \Exception("Note not found.");
        }

        if ($note['courseID'] != $this->courseID) {
            throw new \Exception("Unauthorized: Course Representative can only edit notes belonging to their course.");
        }

        return $notes->update();
    }

  
    public function deleteNotes($noteID): bool
    {
        $notes = new Notes();
        $note = $notes->view($noteID);

        if (!$note) {
            throw new \Exception("Note not found.");
        }

        if ($note['courseID'] != $this->courseID) {
            throw new \Exception("Unauthorized: Course Representative can only delete notes belonging to their course.");
        }

        return $notes->delete($noteID);
    }

   
    public function reviewPeerLearningRequest($requestID, $action)
    {
        $ownsTransaction = false;
        if (!$this->courseRepDAO->inTransaction()) {
            $this->courseRepDAO->beginTransaction();
            $ownsTransaction = true;
        }

        try {
            $plr = new PeerLearningRequest();
            $request = $plr->view($requestID);

            if (!$request) {
                throw new \Exception("Peer learning request not found.");
            }

            if ($action === 'approve' || $action === 'reject') {
                $plr->review($requestID, $action);
            } elseif ($action === 'broadcast_help') {
                $courseUnitID = $request['courseUnitID'];

                // Fetch module name
                $moduleName = $this->courseRepDAO->getCourseUnitName($courseUnitID) ?: $courseUnitID;

                $repBatch = \Models\Student::extractBatchYear($this->enrollmentNo);
                if (!$repBatch) {
                    throw new \Exception("Could not determine batch year from Representative's enrollment number.");
                }

                $message = "Students in Batch {$repBatch} have requested Peer Learning for {$moduleName}. If you can help, please reach out to the Course Rep!";

                $studRows = $this->courseRepDAO->getStudentEnrollmentsForCourse($this->courseID);

                $studentIDs = [];
                foreach ($studRows as $stud) {
                    $studBatch = \Models\Student::extractBatchYear($stud['enrollmentNo']);
                    if ($studBatch !== null && (int)$studBatch <= (int)$repBatch) {
                        $studentIDs[] = $stud['enrollmentNo'];
                    }
                }

                if (!empty($studentIDs)) {
                    $this->sendNotification($studentIDs, $message);
                }

                $this->courseRepDAO->updatePeerLearningRequestStatusCompleted($courseUnitID, $this->repID);
            } else {
                throw new \Exception("Invalid action provided for peer learning request review.");
            }

            if ($ownsTransaction) {
                $this->courseRepDAO->commit();
            }
            return true;
        } catch (\Exception $e) {
            if ($ownsTransaction) {
                $this->courseRepDAO->rollBack();
            }
            throw $e;
        }
    }

    
    public function sendNotification(array $studentIDs, string $message): int
    {
        return $this->courseRepDAO->insertNotifications($studentIDs, $this->repID, $message);
    }
}
