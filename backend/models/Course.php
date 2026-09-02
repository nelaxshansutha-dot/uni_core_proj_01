<?php
namespace Models;
use Config\Database;
use PDO;

class Course {
    private $courseID;
    private $courseName;
    private $dao;

    public function __construct() {
        $this->dao = new \DAO\CourseDAO();
    }

    public function getCourseUnits() {
        return $this->dao->getCourseUnits($this->courseID);
    }
}
