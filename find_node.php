<?php
/**
 * Proxmox Node Discovery Tool
 * Finds the correct node name for your Proxmox installation
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireAuth();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Node Discovery</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f0f;
            color: #eee;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .box {
            background: #1e1e1e;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #333;
        }
        .success {
            background: rgba(34, 221, 119, 0.1);
            border-color: #2d7;
            color: #2dd;
        }
        .error {
            background: rgba(255, 51, 51, 0.1);
            border-color: #f33;
            color: #f66;
        }
        pre {
            background: #0a0a0a;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 12px;
        }
        code {
            background: #2a2a2a;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>🔍 Proxmox Node Discovery</h1>
";

// Try to get cluster nodes
$url = PVE_HOST . '/api2/json/nodes';

echo "<div class='box'>";
echo "<h2>Testing: $url</h2>";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => VERIFY_SSL,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Authorization: PVEAPIToken=' . PVE_TOKEN_ID . '=' . PVE_TOKEN_SECRET
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<div class='error'><strong>cURL Error:</strong> $error</div>";
} elseif ($httpCode >= 400) {
    echo "<div class='error'><strong>HTTP Error $httpCode</strong></div>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    $data = json_decode($response, true);
    
    if (isset($data['data']) && is_array($data['data'])) {
        echo "<div class='success'>";
        echo "<h3>✓ Found " . count($data['data']) . " Node(s):</h3>";
        
        foreach ($data['data'] as $node) {
            $nodeName = $node['node'] ?? 'unknown';
            $status = $node['status'] ?? 'unknown';
            $online = ($status === 'online') ? '✓ ONLINE' : '✗ OFFLINE';
            
            echo "<div class='box'>";
            echo "<h4>Node: <code>$nodeName</code> - $online</h4>";
            echo "<pre>" . htmlspecialchars(json_encode($node, JSON_PRETTY_PRINT)) . "</pre>";
            
            echo "<h4>Update your config.php:</h4>";
            echo "<pre>define('PVE_NODE', '$nodeName');</pre>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<div class='error'>Unexpected response format</div>";
        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
    }
}

echo "</div>";

echo "<div class='box'>";
echo "<h3>Current Configuration:</h3>";
echo "<pre>";
echo "PVE_HOST: " . htmlspecialchars(PVE_HOST) . "\n";
echo "PVE_NODE: " . htmlspecialchars(PVE_NODE) . "\n";
echo "</pre>";
echo "</div>";

echo "</body></html>";
?>
