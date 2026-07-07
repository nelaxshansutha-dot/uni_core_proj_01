<?php
$content = file_get_contents('backend/models/Staff.php');

$updateProfile = <<<EOT
    public function updateAdminProfile(\$realId, \$dept) {
        \$stmt = \$this->conn->prepare("UPDATE Staff SET dept = ? WHERE userID = ?");
        return \$stmt->execute([\$dept, \$realId]);
    }
}
EOT;

$content = preg_replace('/}\s*\?>\s*$/ism', $updateProfile . "\n?>", $content);
file_put_contents('backend/models/Staff.php', $content);

$adminController = file_get_contents('backend/controllers/AdminController.php');

$updateUser = <<<EOT
    public function updateUser(\$id, \$data, \$adminId) {
        Validator::validateRequired(['email', 'first_name', 'last_name'], \$data);

        // Handle rep_ prefix if they edit the rep row
        \$isRepRow = strpos((string)\$id, 'rep_') === 0;
        \$realId = \$isRepRow ? (int)str_replace('rep_', '', \$id) : (int)\$id;

        \$userModel = new User();
        \$role = \$userModel->getRole(\$realId);
        if (!\$role) {
            Response::error("User not found", 404);
        }

        \$db = (new Database())->getConnection();
        \$db->beginTransaction();

        try {
            // 1. Update Core User Info
            \$userModel->updateProfile(\$realId, \$data);

            // 2. Update Role-Specific Info
            if (\$role === User::ROLE_STAFF) {
                \$dept = isset(\$data['department']) ? \$data['department'] : '';
                \$staffModel = new Staff();
                \$staffModel->updateAdminProfile(\$realId, \$dept);
            } else if (\$role === User::ROLE_STUDENT || \$role === User::ROLE_REP) {
                require_once __DIR__ . '/../models/Student.php';
                \$studentModel = new Student();
                
                \$enrollmentNo = isset(\$data['enrollment_no']) ? \$data['enrollment_no'] : null;
                \$courseID = isset(\$data['course']) ? \$data['course'] : null;
                \$std_year = isset(\$data['year']) ? \$data['year'] : null;
                
                \$studentModel->updateAdminProfile(\$realId, \$enrollmentNo, \$courseID, \$std_year);
            }

            \$db->commit();
            Response::success("User updated successfully.");
        } catch (Exception \$e) {
            \$db->rollBack();
            Response::error("Failed to update user: " . \$e->getMessage(), 500);
        }
    }
EOT;

// Replace updateUser method in AdminController.php
$adminController = preg_replace('/public function updateUser\(\$id,\s*\$data,\s*\$adminId\).*?Response::error\("Failed to update user: " \. \$e->getMessage\(\),\s*500\);\s*}/s', ltrim($updateUser), $adminController);

file_put_contents('backend/controllers/AdminController.php', $adminController);

echo "AdminController and Staff models updated\n";
