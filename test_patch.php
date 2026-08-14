<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/uni_core_proj_01/backend/api/admin/users-status?id=45");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['is_active' => false, 'reason' => 'test']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Skip auth middleware for testing
// I will temporarily modify AuthMiddleware to let this pass if it's localhost, or I'll just use the DB directly to see if the query fails.
$result = curl_exec($ch);
echo "Response: " . $result . "\n";
echo "Code: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
