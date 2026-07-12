<?php

namespace Models;

use Exception;
use Config\Database;
use PDO;

class UserFactory {
   
    public static function create(string $role, array $data = []): User {
        switch ($role) {
            case 'admin':
                $user = new Admin();
                break;
            case 'staff':
                $user = new Staff();
                break;
            case 'course_representative':
                $user = new CourseRepresentative();
                break;
            case 'student':
            default:
                $user = new Student();
                break;
        }
        
        if (!empty($data)) {
            $user->hydrate($data);
        }
        
        return $user;
    }

   
    public static function loadByIdentifier(string $identifier, string $role) {
        $db = Database::getInstance()->getConnection();
        
        if ($role === 'student') {
            $sql = "SELECT u.*, s.enrollmentNo, s.courseID, s.std_year 
                    FROM users u 
                    JOIN student s ON u.userID = s.userID 
                    WHERE s.enrollmentNo = :identifier";
        } elseif ($role === 'course_representative') {
            $sql = "SELECT u.*, s.enrollmentNo, s.courseID, s.std_year, c.repID, c.rep_id_string, c.is_first_login
                    FROM users u 
                    JOIN student s ON u.userID = s.userID 
                    JOIN course_representative c ON u.userID = c.userID
                    WHERE s.enrollmentNo = :identifier OR c.rep_id_string = :identifier";
        } elseif ($role === 'staff') {
            $sql = "SELECT u.*, st.staffID FROM users u JOIN staff st ON u.userID = st.userID WHERE st.staffID = :identifier OR u.email = :identifier";
        } elseif ($role === 'admin') {
            $sql = "SELECT u.*, a.adminID FROM users u JOIN admin a ON u.userID = a.userID WHERE a.adminID = :identifier OR u.email = :identifier";
        } else {
            return null;
        }

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':identifier', $identifier);
        $stmt->execute();
        $fullData = $stmt->fetch();

        if ($fullData) {
            return self::create($role, $fullData);
        }

        return null;
    }

   
    public static function loadByEmail(string $email) {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            return self::create($userData['role'], $userData);
        }

        return null;
    }
}
