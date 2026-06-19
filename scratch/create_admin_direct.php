<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Define credentials
    $adminId = 'ADMIN123';
    $adminEmail = 'admin123@unicore.com';
    $plainPassword = 'AdminPassword2026!';
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT userID FROM Users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    
    if ($stmt->rowCount() > 0) {
        // Delete existing to start fresh
        $conn->prepare("DELETE FROM Users WHERE email = ?")->execute([$adminEmail]);
        echo "Deleted existing admin account to recreate it.\n";
    }
    
    // Insert into Users table
    $sql1 = "INSERT INTO Users (fname, lname, email, hash_password, role, is_verified) 
             VALUES ('System', 'Admin', :email, :hash_password, 'admin', TRUE)";
             
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bindParam(':email', $adminEmail);
    $stmt1->bindParam(':hash_password', $hashedPassword);
    
    if ($stmt1->execute()) {
        $userId = $conn->lastInsertId();
        
        // Insert into Admin table
        $sql2 = "INSERT INTO Admin (userID) VALUES (:user_id)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bindParam(':user_id', $userId);
        
        if ($stmt2->execute()) {
            echo "SUCCESS! Admin account created securely in the database.\n";
            echo "--------------------------------------------------\n";
            echo "Login ID (Staff ID): " . $adminId . "\n";
            echo "Login Password: " . $plainPassword . "\n";
            echo "--------------------------------------------------\n";
            echo "You can now log in using these credentials.";
        } else {
            echo "Error linking to Admin table.";
        }
    } else {
        echo "Error creating user in Users table.";
    }

} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
