<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/proxmox_api.php';

// Start session and require authentication
startSecureSession();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Not authenticated'
    ]);
    exit;
}

// Verify CSRF token
$csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid CSRF token'
    ]);
    exit;
}

try {
    // Get status for VMs the user has access to
    $userVMs = getUserVMs();
    $vmids = array_keys($userVMs);
    
    if (empty($vmids)) {
        echo json_encode([
            'status' => 'ok',
            'data' => []
        ]);
        exit;
    }
    
    $statuses = getMultipleVmStatus($vmids);
    
    echo json_encode([
        'status' => 'ok',
        'data' => $statuses
    ]);
} catch (Exception $e) {
    error_log("Status Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An internal error occurred.'
    ]);
}
