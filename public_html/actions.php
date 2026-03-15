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
    $username = $_SESSION['user'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $safeUsername = str_replace(array("\r", "\n", "%0d", "%0a"), ' ', $username);
    $safeAction = str_replace(array("\r", "\n", "%0d", "%0a"), ' ', $action);
    error_log("AUDIT: User '{$safeUsername}' (IP: {$ip}) successfully executed '{$safeAction}' on VM {$vmid}.");
    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    $username = $_SESSION['user'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $safeUsername = str_replace(array("\r", "\n", "%0d", "%0a"), ' ', $username);
    $safeAction = str_replace(array("\r", "\n", "%0d", "%0a"), ' ', $action);
    $safeError = str_replace(array("\r", "\n", "%0d", "%0a"), ' ', $e->getMessage());
    error_log("AUDIT: User '{$safeUsername}' (IP: {$ip}) failed to execute '{$safeAction}' on VM {$vmid}. Error: " . $safeError);
    error_log("VM Action Error: " . $safeError);
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while executing the action.'
    ]);
}