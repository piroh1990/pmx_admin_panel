<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/proxmox_api.php';

// Ensure JSON response content type
header('Content-Type: application/json; charset=utf-8');

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
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
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
    $safeError = str_replace(array("\r", "\n", "%0d", "%0a"), ' ', $e->getMessage());
    error_log("Status Error: " . $safeError);
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An internal error occurred.'
    ]);
}
