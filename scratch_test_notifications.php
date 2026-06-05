<?php
require_once __DIR__ . '/backend/config/Database.php';
require_once __DIR__ . '/backend/controllers/ProfileController.php';
require_once __DIR__ . '/backend/controllers/PeerLearningController.php';

$stage = isset($argv[1]) ? $argv[1] : '';

$db = (new Database())->getConnection();

if ($stage === '--stage1') {
    echo "=== STAGE 1: Setup student (ID 2) and rep (ID 3) ===\n";
    // Backup original roles and preferences if they exist
    $stmt = $db->query("SELECT id, role, lost_item_sms_notification, peer_learning_app_notification FROM users WHERE id IN (2, 3)");
    file_put_contents('scratch_backup.json', json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)));

    // Update ID 3 to rep
    $db->exec("UPDATE users SET role = 'rep', peer_learning_app_notification = 1 WHERE id = 3");
    
    // Update ID 2 preferences
    $profileCtrl = new ProfileController();
    $studentData = [
        'first_name' => 'Arani',
        'last_name' => 'Sivasakthivel',
        'phone_number' => '767844066',
        'lost_item_sms_notification' => 1,
        'peer_learning_app_notification' => 1
    ];
    $profileCtrl->updateProfile($studentData, 2);
    echo "Stage 1 Complete: User preferences set up.\n";
    exit(0);
}

if ($stage === '--stage2') {
    echo "=== STAGE 2: Student creates a peer learning request ===\n";
    // Check initial notification count for rep (ID 3)
    $stmt = $db->prepare("SELECT COUNT(*) FROM notification_recipients WHERE user_id = ?");
    $stmt->execute([3]);
    $countBefore = $stmt->fetchColumn();
    file_put_contents('scratch_count_before.txt', $countBefore);

    $peerCtrl = new PeerLearningController();
    $requestData = [
        'course_code' => 'CS101',
        'topic' => 'Database Normalization',
        'description' => 'Need help with 3NF and BCNF'
    ];
    // This will trigger notification to rep (ID 3) and exit.
    $peerCtrl->createRequest($requestData, 2);
}

if ($stage === '--stage2-verify') {
    echo "=== STAGE 2 VERIFICATION ===\n";
    $countBefore = (int)file_get_contents('scratch_count_before.txt');
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM notification_recipients WHERE user_id = ?");
    $stmt->execute([3]);
    $countAfter = $stmt->fetchColumn();

    echo "Rep notification count before: $countBefore\n";
    echo "Rep notification count after: $countAfter\n";
    
    if ($countAfter > $countBefore) {
        echo "SUCCESS: Representative was notified of the new peer learning request!\n";
        
        // Let's get the last notification message
        $stmtNotif = $db->prepare("SELECT n.title, n.message FROM notifications n JOIN notification_recipients r ON n.id = r.notification_id WHERE r.user_id = ? ORDER BY n.id DESC LIMIT 1");
        $stmtNotif->execute([3]);
        $notif = $stmtNotif->fetch(PDO::FETCH_ASSOC);
        echo "Notification Title: " . $notif['title'] . "\n";
        echo "Notification Msg: " . $notif['message'] . "\n";
    } else {
        echo "FAILED: Representative was NOT notified.\n";
    }
    exit(0);
}

if ($stage === '--stage3') {
    echo "=== STAGE 3: Rep approves the peer learning request ===\n";
    // Find the request ID
    $stmt = $db->query("SELECT id FROM peer_learning_requests WHERE student_id = 2 ORDER BY id DESC LIMIT 1");
    $requestId = $stmt->fetchColumn();
    
    // Assign Rep first
    $db->prepare("UPDATE peer_learning_requests SET rep_id = 3 WHERE id = ?")->execute([$requestId]);

    // Check initial notification count for student (ID 2)
    $stmtCount = $db->prepare("SELECT COUNT(*) FROM notification_recipients WHERE user_id = ?");
    $stmtCount->execute([2]);
    $countBefore = $stmtCount->fetchColumn();
    file_put_contents('scratch_student_count_before.txt', $countBefore);

    $peerCtrl = new PeerLearningController();
    $updateData = [
        'id' => $requestId,
        'status' => 'approved'
    ];
    // This will trigger notification to student (ID 2) and exit
    $peerCtrl->updateStatus($updateData, 3);
}

if ($stage === '--stage3-verify') {
    echo "=== STAGE 3 VERIFICATION ===\n";
    $countBefore = (int)file_get_contents('scratch_student_count_before.txt');
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM notification_recipients WHERE user_id = ?");
    $stmt->execute([2]);
    $countAfter = $stmt->fetchColumn();

    echo "Student notification count before: $countBefore\n";
    echo "Student notification count after: $countAfter\n";
    
    if ($countAfter > $countBefore) {
        echo "SUCCESS: Student was notified of the status update!\n";
        
        // Let's get the last notification message
        $stmtNotif = $db->prepare("SELECT n.title, n.message FROM notifications n JOIN notification_recipients r ON n.id = r.notification_id WHERE r.user_id = ? ORDER BY n.id DESC LIMIT 1");
        $stmtNotif->execute([2]);
        $notif = $stmtNotif->fetch(PDO::FETCH_ASSOC);
        echo "Notification Title: " . $notif['title'] . "\n";
        echo "Notification Msg: " . $notif['message'] . "\n";
    } else {
        echo "FAILED: Student was NOT notified.\n";
    }
    exit(0);
}

if ($stage === '--stage4') {
    echo "=== STAGE 4: Student disables notifications and Rep updates status again ===\n";
    // Disable peer learning notifications for student (ID 2)
    $db->exec("UPDATE users SET peer_learning_app_notification = 0 WHERE id = 2");

    // Find the request ID
    $stmt = $db->query("SELECT id FROM peer_learning_requests WHERE student_id = 2 ORDER BY id DESC LIMIT 1");
    $requestId = $stmt->fetchColumn();

    // Check notification count for student (ID 2)
    $stmtCount = $db->prepare("SELECT COUNT(*) FROM notification_recipients WHERE user_id = ?");
    $stmtCount->execute([2]);
    $countBefore = $stmtCount->fetchColumn();
    file_put_contents('scratch_student_count_before_stage4.txt', $countBefore);

    $peerCtrl = new PeerLearningController();
    $updateData = [
        'id' => $requestId,
        'status' => 'rejected'
    ];
    // This will run and exit
    $peerCtrl->updateStatus($updateData, 3);
}

if ($stage === '--stage4-verify') {
    echo "=== STAGE 4 VERIFICATION ===\n";
    $countBefore = (int)file_get_contents('scratch_student_count_before_stage4.txt');
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM notification_recipients WHERE user_id = ?");
    $stmt->execute([2]);
    $countAfter = $stmt->fetchColumn();

    echo "Student notification count before: $countBefore\n";
    echo "Student notification count after: $countAfter\n";
    
    if ($countAfter == $countBefore) {
        echo "SUCCESS: Student was NOT notified because notifications were disabled!\n";
    } else {
        echo "FAILED: Student WAS notified despite disabling notifications.\n";
    }
    exit(0);
}

if ($stage === '--stage5') {
    echo "=== STAGE 5: Clean up ===\n";
    // Delete test peer learning requests for student 2
    $db->exec("DELETE FROM peer_learning_requests WHERE student_id = 2");
    
    // Restore original roles and preferences
    if (file_exists('scratch_backup.json')) {
        $backup = json_decode(file_get_contents('scratch_backup.json'), true);
        foreach ($backup as $user) {
            $stmt = $db->prepare("UPDATE users SET role = ?, lost_item_sms_notification = ?, peer_learning_app_notification = ? WHERE id = ?");
            $stmt->execute([
                $user['role'],
                $user['lost_item_sms_notification'],
                $user['peer_learning_app_notification'],
                $user['id']
            ]);
        }
        unlink('scratch_backup.json');
    }
    
    // Delete temp files
    @unlink('scratch_count_before.txt');
    @unlink('scratch_student_count_before.txt');
    @unlink('scratch_student_count_before_stage4.txt');
    
    echo "Stage 5 Complete: Database and temp files cleaned up.\n";
    exit(0);
}

echo "Invalid stage argument. Use --stage1, --stage2, --stage2-verify, --stage3, --stage3-verify, --stage4, --stage4-verify, or --stage5.\n";
?>
