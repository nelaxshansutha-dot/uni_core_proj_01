<?php
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = (new Database())->getConnection();

    // Helper function to get foreign key name
    function getForeignKeyName($conn, $table, $column) {
        $stmt = $conn->prepare("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = :table 
              AND COLUMN_NAME = :column
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $stmt->execute([':table' => $table, ':column' => $column]);
        return $stmt->fetchColumn();
    }

    echo "Starting schema update...\n";

    // Drop FK from Notes
    $fkNotes = getForeignKeyName($db, 'Notes', 'courseCode');
    if ($fkNotes) {
        $db->exec("ALTER TABLE Notes DROP FOREIGN KEY $fkNotes");
        echo "Dropped FK $fkNotes from Notes\n";
    }

    // Drop FK from Peer_learning_request
    $fkPlr = getForeignKeyName($db, 'Peer_learning_request', 'courseCode');
    if ($fkPlr) {
        $db->exec("ALTER TABLE Peer_learning_request DROP FOREIGN KEY $fkPlr");
        echo "Dropped FK $fkPlr from Peer_learning_request\n";
    }

    // Rename in Course_units (Primary Key)
    $stmt = $db->query("SHOW COLUMNS FROM Course_units LIKE 'courseCode'");
    if ($stmt->fetch()) {
        $db->exec("ALTER TABLE Course_units CHANGE courseCode courseUnitID VARCHAR(20)");
        echo "Renamed courseCode to courseUnitID in Course_units\n";
    }

    // Rename in Notes (Foreign Key)
    $stmt = $db->query("SHOW COLUMNS FROM Notes LIKE 'courseCode'");
    if ($stmt->fetch()) {
        $db->exec("ALTER TABLE Notes CHANGE courseCode courseUnitID VARCHAR(20) NOT NULL");
        echo "Renamed courseCode to courseUnitID in Notes\n";
    }

    // Rename in Peer_learning_request (Foreign Key)
    $stmt = $db->query("SHOW COLUMNS FROM Peer_learning_request LIKE 'courseCode'");
    if ($stmt->fetch()) {
        // Based on setup_tasks45_db.php, it could be nullable
        $db->exec("ALTER TABLE Peer_learning_request CHANGE courseCode courseUnitID VARCHAR(20) NULL");
        echo "Renamed courseCode to courseUnitID in Peer_learning_request\n";
    }

    // Re-add FK to Notes
    try {
        $db->exec("ALTER TABLE Notes ADD CONSTRAINT fk_notes_courseunit FOREIGN KEY (courseUnitID) REFERENCES Course_units(courseUnitID) ON DELETE CASCADE");
        echo "Added FK to Notes\n";
    } catch (PDOException $e) {
        echo "Failed to add FK to Notes: " . $e->getMessage() . "\n";
    }

    // Re-add FK to Peer_learning_request
    try {
        // Ensure Peer_learning_request has the column before adding FK
        $stmt = $db->query("SHOW COLUMNS FROM Peer_learning_request LIKE 'courseUnitID'");
        if ($stmt->fetch()) {
            $db->exec("ALTER TABLE Peer_learning_request ADD CONSTRAINT fk_plr_courseunit FOREIGN KEY (courseUnitID) REFERENCES Course_units(courseUnitID) ON DELETE CASCADE");
            echo "Added FK to Peer_learning_request\n";
        }
    } catch (PDOException $e) {
        echo "Failed to add FK to Peer_learning_request: " . $e->getMessage() . "\n";
    }

    echo "Schema update complete!\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
