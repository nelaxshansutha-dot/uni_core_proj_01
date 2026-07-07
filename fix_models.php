<?php
$keys = [
    'Course.php' => 'courseUnitID',
    'CourseRep.php' => 'repID',
    'LostItem.php' => 'lostID',
    'Marketplace.php' => 'productID',
    'Notification.php' => 'id',
    'PeerLearning.php' => 'requestID',
    'Staff.php' => 'staffID',
    'Student.php' => 'enrollmentNo',
    'User.php' => 'userID',
    'Note.php' => 'noteID'
];

foreach ($keys as $file => $pk) {
    $path = 'backend/models/' . $file;
    if (!file_exists($path)) continue;
    $content = file_get_contents($path);
    
    // First remove old findById and findByIdBase if they exist
    $content = preg_replace("/\/\/ Polymorphism:.*?public function findById\(\\\$id\)[^}]+}/s", "", $content);
    $content = preg_replace("/\/\/ Polymorphism:.*?public function findById\(\\\$id\)[^}]+}/s", "", $content); // in case
    $content = preg_replace("/public function findById\(\\\$id\)[^}]+}/s", "", $content);
    
    // Add getPrimaryKey below getTableName
    if (strpos($content, 'getPrimaryKey') === false) {
        $replacement = <<<EOT
getTableName() {
        return "$1";
    }

    // Encapsulation: Define the primary key internally
    protected function getPrimaryKey() {
        return "$pk";
    }
EOT;
        $content = preg_replace('/getTableName\(\)\s*\{\s*return\s*"([^"]+)";\s*\}/', $replacement, $content);
    }
    
    file_put_contents($path, $content);
    echo "Fixed $file with PK $pk\n";
}
