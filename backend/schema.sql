-- Database Schema for uni_core_proj_01 backend
-- Source of Truth

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `revoked_tokens`;
DROP TABLE IF EXISTS `marketplace`;
DROP TABLE IF EXISTS `sms_notification`;
DROP TABLE IF EXISTS `lost_items`;
DROP TABLE IF EXISTS `app_notification`;
DROP TABLE IF EXISTS `peer_learning_request`;
DROP TABLE IF EXISTS `notes`;
DROP TABLE IF EXISTS `course_units`;
DROP TABLE IF EXISTS `course`;
DROP TABLE IF EXISTS `otp_verification`;
DROP TABLE IF EXISTS `course_representative`;
DROP TABLE IF EXISTS `student`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `admin`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
    `userID` INT AUTO_INCREMENT PRIMARY KEY,
    `fname` VARCHAR(100) NOT NULL,
    `lname` VARCHAR(100) NOT NULL,
    `phoneNum` VARCHAR(20),
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `hash_password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'staff', 'student', 'course_representative') NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `is_verified` TINYINT(1) DEFAULT 0,
    `last_login` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `peer_learning_app_notification` TINYINT(1) DEFAULT 1,
    `lost_item_sms_notification` TINYINT(1) DEFAULT 1,
    `has_seen_lost_item_popup` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `course` (
    `courseID` INT AUTO_INCREMENT PRIMARY KEY,
    `courseName` VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `course_units` (
    `courseUnitID` INT AUTO_INCREMENT PRIMARY KEY,
    `courseID` INT NOT NULL,
    `courseUniName` VARCHAR(150) NOT NULL,
    `academicYear` VARCHAR(50),
    `semester` VARCHAR(50),
    FOREIGN KEY (`courseID`) REFERENCES `course`(`courseID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `admin` (
    `adminID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    FOREIGN KEY (`userID`) REFERENCES `users`(`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `staff` (
    `staffID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    FOREIGN KEY (`userID`) REFERENCES `users`(`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `student` (
    `enrollmentNo` VARCHAR(50) PRIMARY KEY,
    `userID` INT NOT NULL,
    `courseID` INT NOT NULL,
    `std_year` INT,
    FOREIGN KEY (`userID`) REFERENCES `users`(`userID`) ON DELETE CASCADE,
    FOREIGN KEY (`courseID`) REFERENCES `course`(`courseID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `course_representative` (
    `repID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    `enrollmentNo` VARCHAR(50) NOT NULL,
    `courseID` INT NOT NULL,
    `rep_id_string` VARCHAR(50),
    `hash_password` VARCHAR(255),
    `is_first_login` TINYINT(1) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`userID`) REFERENCES `users`(`userID`) ON DELETE CASCADE,
    FOREIGN KEY (`enrollmentNo`) REFERENCES `student`(`enrollmentNo`) ON DELETE CASCADE,
    FOREIGN KEY (`courseID`) REFERENCES `course`(`courseID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `otp_verification` (
    `otpID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    `otp_code` VARCHAR(10) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `expired_at` DATETIME NOT NULL,
    `verified_at` DATETIME NULL,
    FOREIGN KEY (`userID`) REFERENCES `users`(`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `notes` (
    `noteID` INT AUTO_INCREMENT PRIMARY KEY,
    `enrollmentNo` VARCHAR(50) NOT NULL,
    `courseID` INT NOT NULL,
    `courseUnitID` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `file_url` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `status` VARCHAR(50) DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`enrollmentNo`) REFERENCES `student`(`enrollmentNo`) ON DELETE CASCADE,
    FOREIGN KEY (`courseID`) REFERENCES `course`(`courseID`) ON DELETE CASCADE,
    FOREIGN KEY (`courseUnitID`) REFERENCES `course_units`(`courseUnitID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `peer_learning_request` (
    `requestID` INT AUTO_INCREMENT PRIMARY KEY,
    `courseUnitID` INT NOT NULL,
    `enrollmentNo` VARCHAR(50) NOT NULL,
    `repID` INT NOT NULL,
    `std_year` INT,
    `status` VARCHAR(50) DEFAULT 'pending',
    `courseUnitName` VARCHAR(150),
    `semester` VARCHAR(50),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`courseUnitID`) REFERENCES `course_units`(`courseUnitID`) ON DELETE CASCADE,
    FOREIGN KEY (`enrollmentNo`) REFERENCES `student`(`enrollmentNo`) ON DELETE CASCADE,
    FOREIGN KEY (`repID`) REFERENCES `course_representative`(`repID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `app_notification` (
    `appID` INT AUTO_INCREMENT PRIMARY KEY,
    `repID` INT,
    `enrollmentNo` VARCHAR(50) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`repID`) REFERENCES `course_representative`(`repID`) ON DELETE SET NULL,
    FOREIGN KEY (`enrollmentNo`) REFERENCES `student`(`enrollmentNo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `lost_items` (
    `lostID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    `lostItemName` VARCHAR(150) NOT NULL,
    `last_seen_datetime` DATETIME,
    `last_seen_place` VARCHAR(150),
    `description` TEXT,
    `item_image` VARCHAR(255),
    `contact_number` VARCHAR(20),
    `status` VARCHAR(50) DEFAULT 'lost',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `users`(`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sms_notification` (
    `smsID` INT AUTO_INCREMENT PRIMARY KEY,
    `lostID` INT NOT NULL,
    `userID` INT NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lostID`) REFERENCES `lost_items`(`lostID`) ON DELETE CASCADE,
    FOREIGN KEY (`userID`) REFERENCES `users`(`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `marketplace` (
    `productID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    `productName` VARCHAR(150) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `condition_type` VARCHAR(50),
    `location` VARCHAR(150),
    `image_url` VARCHAR(255),
    `image_url2` VARCHAR(255),
    `image_url3` VARCHAR(255),
    `image_url4` VARCHAR(255),
    `usage_duration` VARCHAR(50),
    `is_flagged` TINYINT(1) DEFAULT 0,
    `description` TEXT,
    `status` VARCHAR(50) DEFAULT 'available',
    `phone_number` VARCHAR(20),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `users`(`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `revoked_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `jti` VARCHAR(255) NOT NULL UNIQUE,
    `expires_at` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
