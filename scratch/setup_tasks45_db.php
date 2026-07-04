<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = (new Database())->getConnection();

    // 1. Create app_notification table
    $sql1 = "CREATE TABLE IF NOT EXISTS app_notification (
        NotificationID INT AUTO_INCREMENT PRIMARY KEY,
        SenderID INT NOT NULL,
        ReceiverID INT NOT NULL,
        NotificationMessage TEXT NOT NULL,
        IsRead TINYINT(1) DEFAULT 0,
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (SenderID) REFERENCES Users(userID) ON DELETE CASCADE,
        FOREIGN KEY (ReceiverID) REFERENCES Users(userID) ON DELETE CASCADE
    )";
    $db->exec($sql1);
    echo "Created app_notification table.\n";

    // 2. We need to check if Peer_learning_request has the necessary columns.
    // If not, we alter it to match what we need.
    // Currently, Peer_learning_request has: requestID, repID, enrollmentNo, topic, status, created_at.
    // The prompt mentions: RequestID, StudentID, Year, Semester, CourseUnitID, CreatedAt.
    // We already have enrollmentNo, which identifies the student.
    // We'll add courseUnitID (from Course_units), std_year, and semester if missing.
    $sql2 = "ALTER TABLE Peer_learning_request 
             ADD COLUMN courseUnitID VARCHAR(20) NULL AFTER enrollmentNo,
             ADD COLUMN std_year INT NULL AFTER courseUnitID,
             ADD COLUMN semester INT NULL AFTER std_year,
             ADD FOREIGN KEY (courseUnitID) REFERENCES Course_units(courseUnitID) ON DELETE CASCADE";
    
    try {
        $db->exec($sql2);
        echo "Altered Peer_learning_request table successfully.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Peer_learning_request already altered.\n";
        } else {
            echo "Error altering Peer_learning_request: " . $e->getMessage() . "\n";
        }
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
