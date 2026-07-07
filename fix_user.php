<?php
$content = file_get_contents('backend/models/User.php');

// Add Role constants
$roles = <<<EOT
class User extends BaseModel {
    public const ROLE_ADMIN = 'admin';
    public const ROLE_STUDENT = 'student';
    public const ROLE_REP = 'rep';
    public const ROLE_STAFF = 'staff';
EOT;
$content = str_replace('class User extends BaseModel {', $roles, $content);

// Add primary key
$pk = <<<EOT
getTableName() {
        return "Users";
    }

    // Encapsulation: Define the primary key internally
    protected function getPrimaryKey() {
        return "userID";
    }
EOT;
$content = preg_replace('/getTableName\(\)\s*\{\s*return\s*"Users";\s*\}/', $pk, $content);

// Replace updateAdminProfile with updateProfile
$updateProfile = <<<EOT
    public function updateProfile(\$realId, \$data) {
        \$phone = isset(\$data['phone_number']) ? \$data['phone_number'] : null;
        \$stmt = \$this->conn->prepare("UPDATE Users SET email = ?, phoneNum = ?, fname = ?, lname = ? WHERE userID = ?");
        return \$stmt->execute([\$data['email'], \$phone, \$data['first_name'], \$data['last_name'], \$realId]);
    }
EOT;

$content = preg_replace('/public function updateAdminProfile[^{]+{[^{]*{[^{]*}[^{]*if\s*\(\$role === \'staff\'\)[^{]*{[^{]*}\s*\$this->conn->commit\(\);\s*return true;\s*}\s*catch\s*\(Exception \$e\)\s*{\s*\$this->conn->rollBack\(\);\s*throw \$e;\s*}\s*}/ism', $updateProfile, $content);

file_put_contents('backend/models/User.php', $content);
echo "User.php fixed\n";
