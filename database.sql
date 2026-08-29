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

CREATE TABLE sms_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_token VARCHAR(255) NOT NULL,
    api_url VARCHAR(255) NOT NULL,
    sender_id VARCHAR(50) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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

-- Insert default SMS settings
INSERT INTO sms_settings (api_token, api_url, sender_id) VALUES ('4810|NgGYVtUHjSS98YTck7nLSlYG9NgjUiv5agw5Enje1071d5c9', 'https://app.text.lk/api/v3/sms/send', 'TextLKDemo');

-- Insert Default Course
INSERT INTO Course (courseName) VALUES ('Computer Science and Technology');

-- Insert Course Units for 1st Year, 1st Semester
INSERT INTO Course_units (courseUnitID, courseID, courseUnitName, academicYear, semester) VALUES
('CST 102-2', 1, 'Introduction to Computer Science', 1, 1),
('CST 101-2', 1, 'Fundamentals of Electronics', 1, 1),
('CST 121-3', 1, 'Structured Programming', 1, 1),
('CST 111-2', 1, 'Essential Mathematics', 1, 1),
('ESD 121-2', 1, 'English Language Level I', 1, 1),
('CST 122-2', 1, 'Web Programming', 1, 1),
('CST 131-2', 1, 'Fundamentals of Computer Networks', 1, 1);

-- Insert 'Science and Technology' Course
INSERT INTO Course (courseName) VALUES ('Science and Technology');

-- Insert Course Units for Science and Technology (2nd Year, 1st Semester)
INSERT INTO Course_units (courseUnitID, courseID, courseUnitName, academicYear, semester) VALUES
('SCT 201-1', 2, 'Abstract Algebra', 2, 1),
('SCT 211-2', 2, 'Statistical Methods', 2, 1),
('SCT 221-1', 2, 'Microbiology - I', 2, 1),
('SCT 222-2', 2, 'Biochemistry', 2, 1),
('SCT 231-2', 2, 'Physical Chemistry', 2, 1),
('SCT 252-1', 2, 'Optics', 2, 1),
('SCT 251-2', 2, 'Electricity and Magnetism', 2, 1),
('SCT 261-1', 2, 'Database Management Systems', 2, 1);

-- Insert Course Units for Science and Technology (2nd Year, 2nd Semester)
INSERT INTO Course_units (courseUnitID, courseID, courseUnitName, academicYear, semester) VALUES
('SCT 202-3', 2, 'Differential Equations and Applications', 2, 2),
('SCT 212-1', 2, 'Operational Research', 2, 2),
('SCT 223-3', 2, 'Diversity of Life', 2, 2),
('SCT 232-2', 2, 'Organic Chemistry', 2, 2),
('SCT 242-2', 2, 'Engineering Thermodynamics', 2, 2),
('SCT 253-1', 2, 'Basic Electronics', 2, 2),
('SCT 241-2', 2, 'Mechanics of Materials', 2, 2),
('BGE 213-1', 2, 'History', 2, 2),
('BGE 214-1', 2, 'Geography', 2, 2);

-- Insert Course Units for Computer Science and Technology (3rd Year, 1st Semester)
INSERT INTO Course_units (courseUnitID, courseID, courseUnitName, academicYear, semester) VALUES
('CST 328-2', 1, 'Advanced Programming Techniques', 3, 1),
('CST 371-2', 1, 'Human Computer Interaction', 3, 1),
('CST 372-3', 1, 'Intelligent Systems', 3, 1),
('CST 327-2', 1, 'Advanced Database Management Systems', 3, 1),
('CST 381-2', 1, 'Computer Graphics', 3, 1),
('CST 333-2', 1, 'Data and Network Security', 3, 1),
('SCT 384-2', 1, 'Embedded Systems', 3, 1),
('CST 344-2', 1, 'Management Information Systems', 3, 1),
('CST 345-2', 1, 'Mobile Application Development', 3, 1),
('CST 393-2', 1, 'Principles of Management', 3, 1),
('ESD 311-1', 1, 'Communication Skills - II', 3, 1),
('CST 315-2', 1, 'Mathematics for Computing', 3, 1);

-- Insert Course Units for Computer Science and Technology (3rd Year, 2nd Semester)
INSERT INTO Course_units (courseUnitID, courseID, courseUnitName, academicYear, semester) VALUES
('CST 347-2', 1, 'Software Architecture & Design Patterns', 3, 2),
('CST 363-2', 1, 'Computer Systems Architecture', 3, 2),
('CST 394-2', 1, 'Project - II', 3, 2),
('CST 346-2', 1, 'Software Quality Assurance', 3, 2),
('CST 382-3', 1, 'Digital Image Processing', 3, 2),
('CST 364-2', 1, 'Systems Level Programming', 3, 2),
('CST 395-2', 1, 'Research Methodology and Scientific Writing', 3, 2),
('CST 396-1', 1, 'Emerging Technologies in Computer Science and Informatics', 3, 2),
('CST 334-2', 1, 'Mobile Computing', 3, 2),
('CST 316-2', 1, 'Statistical Method - II', 3, 2),
('CST 351-2', 1, 'Parallel and Distributed Computing', 3, 2);
