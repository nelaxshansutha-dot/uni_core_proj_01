-- UniCore Database Schema based on ER Diagram

DROP DATABASE IF EXISTS unicore_db;
CREATE DATABASE unicore_db;
USE unicore_db;

-- ==========================================
-- 1. BASE USERS & INHERITANCE
-- ==========================================
CREATE TABLE Users (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    fname VARCHAR(50) NOT NULL,
    lname VARCHAR(50) NOT NULL,
    phoneNum VARCHAR(20),
    email VARCHAR(100) UNIQUE NOT NULL,
    hash_password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    role ENUM('student', 'staff', 'rep', 'admin') NOT NULL DEFAULT 'student',
    last_login TIMESTAMP NULL
);

CREATE TABLE Admin (
    adminID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

CREATE TABLE Staff (
    staffID VARCHAR(50) PRIMARY KEY,
    userID INT NOT NULL,
    dept VARCHAR(100) NOT NULL,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

-- ==========================================
-- 2. ACADEMICS
-- ==========================================
CREATE TABLE Course (
    courseID INT AUTO_INCREMENT PRIMARY KEY,
    courseName VARCHAR(100) NOT NULL
);

CREATE TABLE Course_units (
    courseUnitID VARCHAR(20) PRIMARY KEY,
    courseID INT NOT NULL,
    courseUnitName VARCHAR(100) NOT NULL,
    academicYear INT NOT NULL,
    semester INT NOT NULL,
    FOREIGN KEY (courseID) REFERENCES Course(courseID) ON DELETE CASCADE
);

-- ==========================================
-- 3. STUDENTS & REPS
-- ==========================================
CREATE TABLE Student (
    enrollmentNo VARCHAR(50) PRIMARY KEY,
    userID INT NOT NULL,
    courseID INT NULL,
    std_year INT NULL,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (courseID) REFERENCES Course(courseID) ON DELETE SET NULL
);

CREATE TABLE Course_representative (
    repID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    enrollmentNo VARCHAR(50) NOT NULL,
    courseID INT NULL,
    hash_password VARCHAR(255),
    is_first_login TINYINT(1) DEFAULT 1,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (enrollmentNo) REFERENCES Student(enrollmentNo) ON DELETE CASCADE,
    FOREIGN KEY (courseID) REFERENCES Course(courseID) ON DELETE SET NULL
);

-- ==========================================
-- 4. VERIFICATION & NOTIFICATIONS
-- ==========================================
CREATE TABLE OTP_verification (
    otpID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    otp_code VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expired_at DATETIME NOT NULL,
    verified_at DATETIME NULL,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

CREATE TABLE App_notification (
    appID INT AUTO_INCREMENT PRIMARY KEY,
    repID INT NOT NULL,
    enrollmentNo VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repID) REFERENCES Course_representative(repID) ON DELETE CASCADE,
    FOREIGN KEY (enrollmentNo) REFERENCES Student(enrollmentNo) ON DELETE CASCADE
);

-- ==========================================
-- 5. FEATURES
-- ==========================================
CREATE TABLE Lost_items (
    lostID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    lostItemName VARCHAR(100) NOT NULL,
    last_seen_date DATE NOT NULL,
    last_seen_time TIME NOT NULL,
    item_image VARCHAR(255),
    contact_no VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

CREATE TABLE SMS_notification (
    smsID INT AUTO_INCREMENT PRIMARY KEY,
    lostID INT NOT NULL,
    userID INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lostID) REFERENCES Lost_items(lostID) ON DELETE CASCADE,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

CREATE TABLE marketplace (
    productID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    productName VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    condition_type VARCHAR(20) NOT NULL DEFAULT 'new',
    location VARCHAR(255) NOT NULL,
    phone_number VARCHAR(30) NOT NULL,
    usage_duration VARCHAR(100) NULL,
    image_url VARCHAR(255),
    image_url2 VARCHAR(255) NULL,
    image_url3 VARCHAR(255) NULL,
    image_url4 VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

CREATE TABLE Notes (
    noteID INT AUTO_INCREMENT PRIMARY KEY,
    enrollmentNo VARCHAR(50) NOT NULL,
    courseID INT NOT NULL,
    courseUnitID VARCHAR(20) NOT NULL,
    title VARCHAR(100) NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollmentNo) REFERENCES Student(enrollmentNo) ON DELETE CASCADE,
    FOREIGN KEY (courseID) REFERENCES Course(courseID) ON DELETE CASCADE,
    FOREIGN KEY (courseUnitID) REFERENCES Course_units(courseUnitID) ON DELETE CASCADE
);

CREATE TABLE Peer_learning_request (
    requestID INT AUTO_INCREMENT PRIMARY KEY,
    repID INT NOT NULL,
    enrollmentNo VARCHAR(50) NOT NULL,
    courseUnitName VARCHAR(200) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repID) REFERENCES Course_representative(repID) ON DELETE CASCADE,
    FOREIGN KEY (enrollmentNo) REFERENCES Student(enrollmentNo) ON DELETE CASCADE
);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- 6. DEFAULT DATA
-- ==========================================
-- Insert Default Admin (Password: PASSWORD)
INSERT INTO Users (fname, lname, email, hash_password, role, is_verified) 
VALUES ('Super', 'Admin', 'admin@unicore.com', '$2y$10$ANYPk2UCXkPfgEhdWDlHceI2h5VcIQ9K7uOqZiEffB8IpEMvnPdqq', 'admin', TRUE);

-- Link default admin to Admin table
INSERT INTO Admin (userID) VALUES (LAST_INSERT_ID());
