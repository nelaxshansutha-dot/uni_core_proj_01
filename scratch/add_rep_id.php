
require_once __DIR__ . '/../backend/config/Database.php';

try {
    $db = (new Database())->getConnection();
    
    $db->exec("ALTER TABLE Course_representative ADD COLUMN rep_id_string VARCHAR(100) UNIQUE NULL AFTER repID");
    echo "Added rep_id_string to Course_representative.\n";

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo $e->getMessage();
    }
}
?>
