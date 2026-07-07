<?php
$c = file_get_contents('backend/controllers/AdminController.php');
$add = "    public function toggleUserStatus(\$id, \$data, \$adminId) {
        if (!isset(\$data['is_active'])) {
            Response::error('Missing is_active flag');
        }
        \$isActive = \$data['is_active'] ? 1 : 0;
        \$userModel = new User();
        \$realId = (int)str_replace('rep_', '', \$id);
        if (strpos((string)\$id, 'rep_') === 0) {
            \$courseRepModel = new CourseRep();
            \$courseRepModel->toggleStatus(\$realId, \$isActive);
        } else {
            \$userModel->toggleStatus(\$realId, \$isActive);
        }
        if (\$isActive === 0 && !empty(\$data['reason'])) {
            \$user = \$userModel->findById(\$realId);
            if (\$user && !empty(\$user['email'])) {
                MailService::sendDeactivationEmail(\$user['email'], \$data['reason']);
            }
        }
        Response::success('User status updated successfully.');
    }

    public function searchStudents(\$query) {
        \$userModel = new User();
        \$students = \$userModel->searchStudents(\$query);
        Response::success('Students found', \$students);
    }

    public function assignRep";

$c = str_replace('public function assignRep', $add, $c);
file_put_contents('backend/controllers/AdminController.php', $c);
echo "Added missing methods to AdminController\n";
