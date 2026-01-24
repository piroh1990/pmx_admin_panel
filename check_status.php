<?php
/**
 * Quick Status Check
 * Shows real-time errors and responses from the API
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

startSecureSession();

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user = getCurrentUser();
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Check - Proxmox Admin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f0f;
            color: #eee;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 {
            color: #fff;
        }
        .info-box {
            background: #1e1e1e;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #333;
        }
        .log-area {
            background: #0a0a0a;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #333;
        }
        .log-entry {
            margin: 5px 0;
            padding: 8px;
            border-radius: 4px;
        }
        .log-entry.info {
            background: rgba(0, 153, 255, 0.1);
            color: #09f;
        }
        .log-entry.success {
            background: rgba(34, 221, 119, 0.1);
            color: #2dd;
        }
        .log-entry.error {
            background: rgba(255, 51, 51, 0.1);
            color: #f66;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        button:hover {
            background: #5568d3;
        }
        .back-btn {
            background: #f90;
        }
        .timestamp {
            color: #888;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Live Status Check</h1>
        
        <div class="info-box">
            <strong>User:</strong> <?= htmlspecialchars($user['name']) ?><br>
            <strong>Config File:</strong> <?= file_exists(__DIR__ . '/config.php') ? '✓ Found' : '✗ Missing' ?><br>
            <strong>Proxmox Host:</strong> <?= htmlspecialchars(PVE_HOST) ?><br>
            <strong>Node:</strong> <?= htmlspecialchars(PVE_NODE) ?>
        </div>
        
        <div>
            <button onclick="testConnection()">🔄 Test API Connection</button>
            <button onclick="testStatusEndpoint()">📊 Test Status Endpoint</button>
            <button onclick="clearLog()">🗑️ Clear Log</button>
            <button class="back-btn" onclick="location.href='admin.php'">← Back to Admin</button>
        </div>
        
        <div class="info-box">
            <h3>Live Log</h3>
            <div id="log" class="log-area">
                <div class="log-entry info">Click a button above to test...</div>
            </div>
        </div>
    </div>
    
    <script>
        const csrfToken = '<?= $csrfToken ?>';
        
        function log(message, type = 'info') {
            const logDiv = document.getElementById('log');
            const entry = document.createElement('div');
            entry.className = 'log-entry ' + type;
            const timestamp = new Date().toLocaleTimeString();
            entry.innerHTML = `<span class="timestamp">[${timestamp}]</span> ${message}`;
            logDiv.appendChild(entry);
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        function clearLog() {
            document.getElementById('log').innerHTML = '';
            log('Log cleared', 'info');
        }
        
        async function testConnection() {
            log('Testing API connection...', 'info');
            
            try {
                const response = await fetch('status.php?csrf_token=' + encodeURIComponent(csrfToken));
                log(`Response Status: ${response.status} ${response.statusText}`, response.ok ? 'success' : 'error');
                
                const text = await response.text();
                log('Raw Response: ' + text.substring(0, 200), 'info');
                
                try {
                    const data = JSON.parse(text);
                    log('Parsed JSON successfully', 'success');
                    log('Response Data: ' + JSON.stringify(data, null, 2), 'info');
                    
                    if (data.status === 'ok') {
                        log('✓ Status endpoint working!', 'success');
                        if (data.data) {
                            const vmCount = Object.keys(data.data).length;
                            log(`Found ${vmCount} VMs`, 'success');
                            
                            for (const [vmid, vmData] of Object.entries(data.data)) {
                                if (vmData.error) {
                                    log(`VM ${vmid}: ERROR - ${vmData.error}`, 'error');
                                } else {
                                    log(`VM ${vmid}: ${vmData.status || 'unknown'}`, 'success');
                                }
                            }
                        }
                    } else {
                        log('✗ Error: ' + data.message, 'error');
                    }
                } catch (parseError) {
                    log('✗ Failed to parse JSON: ' + parseError.message, 'error');
                }
            } catch (error) {
                log('✗ Connection failed: ' + error.message, 'error');
                log('Error details: ' + error.stack, 'error');
            }
        }
        
        async function testStatusEndpoint() {
            log('Testing status endpoint directly...', 'info');
            
            try {
                const url = 'status.php?csrf_token=' + encodeURIComponent(csrfToken);
                log('Fetching: ' + url, 'info');
                
                const response = await fetch(url);
                log('HTTP Status: ' + response.status, response.ok ? 'success' : 'error');
                log('Content-Type: ' + response.headers.get('content-type'), 'info');
                
                const text = await response.text();
                
                if (text.length > 0) {
                    log('Response length: ' + text.length + ' bytes', 'info');
                    
                    try {
                        const json = JSON.parse(text);
                        log('✓ Valid JSON response', 'success');
                        log(JSON.stringify(json, null, 2), 'info');
                    } catch (e) {
                        log('✗ Invalid JSON. Raw response:', 'error');
                        log(text, 'error');
                    }
                } else {
                    log('✗ Empty response', 'error');
                }
            } catch (error) {
                log('✗ Request failed: ' + error.message, 'error');
            }
        }
        
        // Auto-run test on load
        window.addEventListener('load', () => {
            log('Page loaded. Ready to test.', 'success');
        });
    </script>
</body>
</html>
