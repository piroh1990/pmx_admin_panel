<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

// Require authentication
requireAuth();

$user = getCurrentUser();
$userVMs = getUserVMs(); // Get VMs accessible to this user
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Proxmox VM Admin</title>
<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    background: #0f0f0f;
    color: #eee;
    padding: 0;
    margin: 0;
}

.header {
    background: #1e1e1e;
    padding: 15px 30px;
    border-bottom: 1px solid #333;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.header h1 {
    margin: 0;
    font-size: 24px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-name {
    color: #aaa;
    font-size: 14px;
}

.logout-btn {
    background: #f33;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
}

.logout-btn:hover {
    background: #c22;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

.vm {
    background: #1e1e1e;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    border: 1px solid #333;
    transition: border-color 0.3s;
}

.vm.status-running {
    border-left: 4px solid #2d7;
}

.vm.status-stopped {
    border-left: 4px solid #f33;
}

.vm-header {
    font-size: 18px;
    margin-bottom: 15px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.vm-title {
    flex: 1;
}

.vm-status {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.running {
    background: rgba(34, 221, 119, 0.2);
    color: #2dd;
    border: 1px solid #2d7;
}

.status-badge.stopped {
    background: rgba(255, 51, 51, 0.2);
    color: #f66;
    border: 1px solid #f33;
}

.status-badge.loading {
    background: rgba(255, 153, 0, 0.2);
    color: #f90;
    border: 1px solid #f90;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.status-indicator.running {
    background: #2d7;
}

.status-indicator.stopped {
    background: #f33;
}

.status-indicator.loading {
    background: #f90;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.vm-info {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 13px;
    color: #aaa;
    flex-wrap: wrap;
}

.vm-info-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.vm-info-item strong {
    color: #fff;
}

.vm-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

button {
    padding: 10px 18px;
    cursor: pointer;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.start { 
    background: #2d7;
    color: #000;
}
.start:hover { background: #3e8; }

.shutdown { 
    background: #f90;
    color: #000;
}
.shutdown:hover { background: #fa0; }

.reboot { 
    background: #09f;
    color: #fff;
}
.reboot:hover { background: #1af; }

.reset { 
    background: #f33;
    color: #fff;
}
.reset:hover { background: #f44; }

.nav-links {
    display: flex;
    gap: 15px;
    align-items: center;
}

.nav-links a {
    color: #09f;
    text-decoration: none;
    font-size: 14px;
}

.nav-links a:hover { text-decoration: underline; }

.coord-link {
    color: #0cf;
    text-decoration: none;
    font-weight: 600;
    font-family: 'Courier New', Courier, monospace;
}

.coord-link:hover {
    text-decoration: underline;
    color: #4df;
}
</style>
</head>
<body>

<div class="header">
    <h1>🖥️ Proxmox VM Admin</h1>
    <div class="nav-links">
        <a href="fleet.php">Fleet Dispatch</a>
        <a href="missions.php">Missions</a>
        <a href="messages.php">Messages</a>
    </div>
    <div class="user-info">
        <span class="user-name">👤 <?= htmlspecialchars($user['name']) ?></span>
        <form method="POST" action="logout.php" style="margin: 0;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</div>

<div class="container">

<?php foreach ($userVMs as $vmid => $name): ?>
<div class="vm" id="vm-<?= $vmid ?>" data-vmid="<?= $vmid ?>">
    <div class="vm-header">
        <div class="vm-title">
            <strong><?= htmlspecialchars($name) ?></strong> 
            <span style="color: #888;">(ID: <?= $vmid ?>)</span>
        </div>
        <div class="vm-status">
            <span class="status-badge loading">
                <span class="status-indicator loading"></span>
                <span class="status-text">Loading...</span>
            </span>
        </div>
    </div>
    
    <div class="vm-info" style="display: none;">
        <div class="vm-info-item">
            <span>💻 CPU:</span>
            <strong class="cpu-usage">-</strong>
        </div>
        <div class="vm-info-item">
            <span>🧠 Memory:</span>
            <strong class="mem-usage">-</strong>
        </div>
        <div class="vm-info-item">
            <span>⏱️ Uptime:</span>
            <strong class="uptime">-</strong>
        </div>
    </div>
    
    <div class="vm-actions">
        <?php foreach (['start','shutdown','reboot','reset'] as $action): ?>
        <button class="<?= $action ?>"
                onclick="vmAction(<?= $vmid ?>, '<?= $action ?>')">
            <?= ucfirst($action) ?>
        </button>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

</div>

<script>
const csrfToken = '<?= $csrfToken ?>';
let statusInterval;

// Fetch VM statuses
function fetchVmStatuses() {
    fetch('status.php', {
        headers: {
            'X-CSRF-Token': csrfToken
        }
    })
        .then(r => {
            console.log('Status API Response Code:', r.status);
            if (!r.ok) {
                throw new Error(`HTTP error! status: ${r.status}`);
            }
            return r.json();
        })
        .then(d => {
            console.log('Status API Response:', d);
            if (d.status === 'ok') {
                updateVmStatuses(d.data);
            } else {
                console.error('Error fetching statuses:', d.message);
                showError('Failed to fetch VM status: ' + d.message);
            }
        })
        .catch(err => {
            console.error('Error fetching statuses:', err);
            showError('Connection error: ' + err.message);
        });
}

// Show error message
function showError(message) {
    // Check if error banner exists, if not create it
    let errorBanner = document.getElementById('error-banner');
    if (!errorBanner) {
        errorBanner = document.createElement('div');
        errorBanner.id = 'error-banner';
        errorBanner.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #f33;
            color: white;
            padding: 15px;
            text-align: center;
            z-index: 9999;
            font-weight: 600;
        `;
        document.body.prepend(errorBanner);
    }
    errorBanner.textContent = '⚠️ ' + message + ' - Check browser console for details';
    errorBanner.style.display = 'block';
}

// Update VM status display
function updateVmStatuses(statuses) {
    for (const [vmid, data] of Object.entries(statuses)) {
        const vmElement = document.getElementById('vm-' + vmid);
        if (!vmElement) continue;
        
        const statusBadge = vmElement.querySelector('.status-badge');
        const statusIndicator = vmElement.querySelector('.status-indicator');
        const statusText = vmElement.querySelector('.status-text');
        const vmInfo = vmElement.querySelector('.vm-info');
        
        if (data.error) {
            statusBadge.className = 'status-badge stopped';
            statusIndicator.className = 'status-indicator stopped';
            statusText.textContent = 'Error';
            vmElement.className = 'vm status-stopped';
            continue;
        }
        
        const status = data.status || 'unknown';
        const isRunning = status === 'running';
        
        // Update status badge
        statusBadge.className = 'status-badge ' + status;
        statusIndicator.className = 'status-indicator ' + status;
        statusText.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        vmElement.className = 'vm status-' + status;
        
        // Update VM info if running
        if (isRunning && data.cpu !== undefined) {
            vmInfo.style.display = 'flex';
            
            // CPU usage
            const cpuPercent = ((data.cpu || 0) * 100).toFixed(1);
            vmElement.querySelector('.cpu-usage').textContent = cpuPercent + '%';
            
            // Memory usage
            if (data.mem && data.maxmem) {
                const memUsed = (data.mem / 1024 / 1024 / 1024).toFixed(1);
                const memMax = (data.maxmem / 1024 / 1024 / 1024).toFixed(1);
                const memPercent = ((data.mem / data.maxmem) * 100).toFixed(1);
                vmElement.querySelector('.mem-usage').textContent = `${memUsed} GB / ${memMax} GB (${memPercent}%)`;
            }
            
            // Uptime
            if (data.uptime) {
                vmElement.querySelector('.uptime').textContent = formatUptime(data.uptime);
            }
        } else {
            vmInfo.style.display = 'none';
        }
    }
}

// Format uptime in human-readable format
function formatUptime(seconds) {
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    
    const parts = [];
    if (days > 0) parts.push(days + 'd');
    if (hours > 0) parts.push(hours + 'h');
    if (minutes > 0) parts.push(minutes + 'm');
    
    return parts.length > 0 ? parts.join(' ') : '< 1m';
}

// VM action handler
function vmAction(vmid, action) {
    if (!confirm(`Really ${action} VM ${vmid}?`)) return;

    fetch('actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            vmid: vmid,
            action: action,
            csrf_token: csrfToken
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'ok') {
            alert('Action sent successfully');
            // Refresh status after a short delay
            setTimeout(fetchVmStatuses, 2000);
        } else {
            alert('Error: ' + d.message);
        }
    })
    .catch(err => alert('Error: ' + err));
}

// Initial fetch and set up auto-refresh
fetchVmStatuses();
statusInterval = setInterval(fetchVmStatuses, 5000); // Refresh every 5 seconds

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (statusInterval) {
        clearInterval(statusInterval);
    }
});
</script>

</body>
</html>