<?php

namespace Models;

use PDO;

class CourseRepresentative extends Student
{
    private $repID;
    private $rep_id_string;
    private $is_first_login;


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

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        if (!empty($data)) {
            $this->repID = $data['repID'] ?? $this->repID;
            $this->rep_id_string = $data['rep_id_string'] ?? $this->rep_id_string;
            $this->is_first_login = $data['is_first_login'] ?? $this->is_first_login;
        }
    }

    public function register()
    {
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
            $stmt->bindParam(':uid', $this->getUserID());
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
            return $this->getUserID();
        } catch (\Exception $e) {
            if ($ownsTransaction) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    
    public function updateNotes($noteID, array $data): bool
    {
        $notes = new Notes();
        $note = $notes->view($noteID);

        if (!$note) {
            throw new \Exception("Note not found.");
        }

        if ($note['courseID'] != $this->courseID) {
            throw new \Exception("Unauthorized: Course Representative can only edit notes belonging to their course.");
        }

        return $notes->update($noteID, $data);
    }

    /**
     * Delete any note matching the Rep's courseID
     */
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
        if (!$this->conn->inTransaction()) {
            $this->conn->beginTransaction();
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
                $stmtUnit = $this->conn->prepare("SELECT courseUnitName FROM course_units WHERE courseUnitID = :cuid");
                $stmtUnit->execute([':cuid' => $courseUnitID]);
                $moduleName = $stmtUnit->fetchColumn() ?: $courseUnitID;

                $repBatch = \Models\Student::extractBatchYear($this->enrollmentNo);
                if (!$repBatch) {
                    throw new \Exception("Could not determine batch year from Representative's enrollment number.");
                }

                $message = "Students in Batch {$repBatch} have requested Peer Learning for {$moduleName}. If you can help, please reach out to the Course Rep!";

                // Find all students in same course and batch <= Rep's batch
                $stmtStud = $this->conn->prepare("SELECT enrollmentNo FROM student WHERE courseID = :cid");
                $stmtStud->execute([':cid' => $this->courseID]);

                $studentIDs = [];
                while ($stud = $stmtStud->fetch(\PDO::FETCH_ASSOC)) {
                    $studBatch = \Models\Student::extractBatchYear($stud['enrollmentNo']);
                    if ($studBatch !== null && (int)$studBatch <= (int)$repBatch) {
                        $studentIDs[] = $stud['enrollmentNo'];
                    }
                }

                if (!empty($studentIDs)) {
                    $this->sendNotification($studentIDs, $message);
                }

                // Update status to 'completed' for all requests belonging to this courseUnit and this Rep
                $stmtComp = $this->conn->prepare("UPDATE peer_learning_request SET status = 'completed' WHERE courseUnitID = :cuid AND repID = :repid");
                $stmtComp->execute([':cuid' => $courseUnitID, ':repid' => $this->repID]);
            } else {
                throw new \Exception("Invalid action provided for peer learning request review.");
            }

            if ($ownsTransaction) {
                $this->conn->commit();
            }
            return true;
        } catch (\Exception $e) {
            if ($ownsTransaction) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Send bulk notification to a list of students via AppNotification
     */
    public function sendNotification(array $studentIDs, string $message): int
    {
        if (empty($studentIDs)) return 0;

        // Bulk insert into app_notification
        $query = "INSERT INTO app_notification (repID, enrollmentNo, message) VALUES ";
        $values = [];
        $params = [];
        $i = 0;

        foreach ($studentIDs as $enr) {
            $values[] = "(:repid{$i}, :enr{$i}, :msg{$i})";
            $params[":repid{$i}"] = $this->repID;
            $params[":enr{$i}"] = $enr;
            $params[":msg{$i}"] = $message;
            $i++;
        }

        $query .= implode(', ', $values);
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}
