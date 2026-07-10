<?php
require_once __DIR__ . '/backend/controllers/DashboardController.php';

try {
    $controller = new DashboardController();
    $user = ['role' => 'student'];
    $controller->getRecentActivity($user);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
