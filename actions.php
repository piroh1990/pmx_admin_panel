<?php
require_once __DIR__ . '/config.php';
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
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid CSRF token'
    ]);
    exit;
}

$vmid   = (int)($_POST['vmid'] ?? 0);
$action = $_POST['action'] ?? '';

$allowedActions = ['start', 'shutdown', 'reboot', 'reset'];

if (!$vmid || !in_array($action, $allowedActions, true)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
    exit;
}

// Check if user has access to this VM
if (!canAccessVM($vmid)) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Access denied to this VM'
    ]);
    exit;
}

try {
    vmAction($vmid, $action);
    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}