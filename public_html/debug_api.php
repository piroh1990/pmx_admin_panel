<?php
/**
 * API Debug and Test Tool
 * This helps you diagnose issues with the Proxmox API connection
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/proxmox_api.php';

// Start session for authentication (optional - can be disabled for testing)
requireAuth();
$authenticated = true;

$testResults = [];

// Function to test API call and capture errors
function testApiCall($description, $callable) {
    $result = [
        'description' => $description,
        'status' => 'pending',
        'message' => '',
        'data' => null,
        'error' => null,
        'time' => 0
    ];
    
    $startTime = microtime(true);
    
    try {
        $result['data'] = $callable();
        $result['status'] = 'success';
        $result['message'] = 'Success';
    } catch (Exception $e) {
        $result['status'] = 'error';
        $result['error'] = $e->getMessage();
        $result['message'] = $e->getMessage();
    }
    
    $result['time'] = round((microtime(true) - $startTime) * 1000, 2);
    
    return $result;
}

// Run tests
$testResults[] = [
    'section' => 'Configuration',
    'tests' => [
        [
            'description' => 'Proxmox Host',
            'status' => 'info',
            'message' => PVE_HOST,
        ],
        [
            'description' => 'Proxmox Node',
            'status' => 'info',
            'message' => PVE_NODE,
        ],
        [
            'description' => 'API Token ID',
            'status' => 'info',
            'message' => PVE_TOKEN_ID,
        ],
        [
            'description' => 'API Token Secret',
            'status' => 'info',
            'message' => '*** REDACTED ***',
        ],
        [
            'description' => 'SSL Verification',
            'status' => 'info',
            'message' => VERIFY_SSL ? 'Enabled' : 'Disabled',
        ],
    ]
];

// Test API connectivity
$apiTests = [];

// Test 1: Basic connectivity
$apiTests[] = testApiCall('Test API endpoint accessibility', function() {
    $url = PVE_HOST . '/api2/json/version';
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => VERIFY_SSL,
        CURLOPT_SSL_VERIFYHOST => VERIFY_SSL ? 2 : 0,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            'Authorization: PVEAPIToken=' . PVE_TOKEN_ID . '=' . PVE_TOKEN_SECRET
        ],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("cURL Error: $error");
    }
    
    if ($httpCode >= 400) {
        throw new Exception("HTTP Error $httpCode: $response");
    }
    
    $data = json_decode($response, true);
    return $data;
});

// Test 2: List VMs accessible to the user
$userVMs = getUserVMs();
foreach ($userVMs as $vmid => $name) {
    $apiTests[] = testApiCall("Get status for VM $vmid ($name)", function() use ($vmid) {
        return getVmStatus($vmid);
    });
}

$testResults[] = [
    'section' => 'API Tests',
    'tests' => $apiTests
];

// Test 3: Authentication test
$user = getCurrentUser();
$userVMs = getUserVMs();

$testResults[] = [
    'section' => 'Authentication',
    'tests' => [
        [
            'description' => 'User Logged In',
            'status' => 'success',
            'message' => $user['username'] . ' (' . $user['name'] . ')',
        ],
        [
            'description' => 'Accessible VMs',
            'status' => 'info',
            'message' => implode(', ', array_keys($userVMs)),
        ],
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Debug Tool</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f0f;
            color: #eee;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            color: #fff;
            margin-bottom: 10px;
        }
        
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px solid #ffc107;
        }
        
        .section {
            background: #1e1e1e;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #333;
        }
        
        .section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .test-item {
            background: #2a2a2a;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #666;
        }
        
        .test-item.success {
            border-left-color: #2d7;
        }
        
        .test-item.error {
            border-left-color: #f33;
        }
        
        .test-item.info {
            border-left-color: #09f;
        }
        
        .test-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .test-description {
            font-weight: 600;
            color: #fff;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.success {
            background: rgba(34, 221, 119, 0.2);
            color: #2dd;
        }
        
        .status-badge.error {
            background: rgba(255, 51, 51, 0.2);
            color: #f66;
        }
        
        .status-badge.info {
            background: rgba(0, 153, 255, 0.2);
            color: #09f;
        }
        
        .test-message {
            color: #aaa;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .test-data {
            background: #1a1a1a;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .test-time {
            color: #888;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .refresh-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin: 20px 0;
        }
        
        .refresh-btn:hover {
            background: #5568d3;
        }
        
        .login-link {
            background: #f90;
            color: #000;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            margin: 10px 0;
        }
        
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 API Debug Tool</h1>
        
        <div class="warning">
            <strong>⚠️ Security Warning:</strong> This file shows sensitive debug information. Delete it after debugging!
        </div>
        
        <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Tests</button>
        
        <?php foreach ($testResults as $section): ?>
            <div class="section">
                <h2><?= htmlspecialchars($section['section']) ?></h2>
                
                <?php foreach ($section['tests'] as $test): ?>
                    <div class="test-item <?= $test['status'] ?>">
                        <div class="test-header">
                            <span class="test-description">
                                <?= htmlspecialchars($test['description']) ?>
                            </span>
                            <span class="status-badge <?= $test['status'] ?>">
                                <?= $test['status'] ?>
                            </span>
                        </div>
                        
                        <div class="test-message">
                            <?= htmlspecialchars($test['message']) ?>
                        </div>
                        
                        <?php if (isset($test['time']) && $test['time'] > 0): ?>
                            <div class="test-time">
                                ⏱️ Response time: <?= $test['time'] ?> ms
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($test['data']) && $test['data']): ?>
                            <div class="test-data">
                                <strong>Response Data:</strong>
                                <pre><?= htmlspecialchars(json_encode($test['data'], JSON_PRETTY_PRINT)) ?></pre>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($test['error']) && $test['error']): ?>
                            <div class="test-data" style="border-left: 3px solid #f33;">
                                <strong>Error Details:</strong>
                                <pre><?= htmlspecialchars($test['error']) ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="section">
            <h2>Quick Actions</h2>
            <a href="admin.php" class="refresh-btn" style="text-decoration: none; display: inline-block;">
                Go to Admin Panel
            </a>
        </div>
    </div>
</body>
</html>
