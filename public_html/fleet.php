<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

// Require authentication
requireAuth();

$user      = getCurrentUser();
$csrfToken = generateCsrfToken();

// Read and sanitise URL parameters for pre-filling the form
$galaxy   = filter_input(INPUT_GET, 'galaxy',   FILTER_VALIDATE_INT) ?: '';
$system   = filter_input(INPUT_GET, 'system',   FILTER_VALIDATE_INT) ?: '';
$position = filter_input(INPUT_GET, 'position', FILTER_VALIDATE_INT) ?: '';
$mission  = '';

$rawMission = filter_input(INPUT_GET, 'mission', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
if ($rawMission !== '' && isset(MISSION_TYPES[$rawMission])) {
    $mission = $rawMission;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fleet Dispatch - Proxmox VM Admin</title>
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

.header h1 { margin: 0; font-size: 24px; }

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

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-name { color: #aaa; font-size: 14px; }

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

.logout-btn:hover { background: #c22; }

.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 30px 20px;
}

.panel {
    background: #1e1e1e;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    border: 1px solid #333;
}

.panel h2 {
    margin: 0 0 20px;
    font-size: 20px;
    border-bottom: 1px solid #333;
    padding-bottom: 10px;
}

.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.form-group {
    flex: 1;
    min-width: 120px;
}

.form-group label {
    display: block;
    color: #aaa;
    font-size: 13px;
    margin-bottom: 6px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px 12px;
    background: #111;
    border: 1px solid #444;
    border-radius: 6px;
    color: #eee;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #09f;
}

.dispatch-btn {
    margin-top: 10px;
    padding: 12px 24px;
    background: #09f;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.dispatch-btn:hover { background: #1af; }

.prefill-notice {
    background: rgba(0,153,255,0.1);
    border: 1px solid #09f;
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #9cf;
}
</style>
</head>
<body>

<div class="header">
    <h1>🚀 Fleet Dispatch</h1>
    <div class="nav-links">
        <a href="admin.php">Dashboard</a>
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
    <div class="panel">
        <h2>🎯 Dispatch Fleet</h2>

        <?php if ($galaxy !== '' || $system !== '' || $position !== '' || $mission !== ''): ?>
        <div class="prefill-notice">
            ✅ Coordinates and mission have been pre-filled from your link.
        </div>
        <?php endif; ?>

        <form id="fleet-form" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="galaxy">Galaxy</label>
                    <input type="number" id="galaxy" name="galaxy" min="1" max="9"
                           value="<?= htmlspecialchars((string) $galaxy) ?>" placeholder="1-9" required>
                </div>
                <div class="form-group">
                    <label for="system">System</label>
                    <input type="number" id="system" name="system" min="1" max="499"
                           value="<?= htmlspecialchars((string) $system) ?>" placeholder="1-499" required>
                </div>
                <div class="form-group">
                    <label for="position">Position</label>
                    <input type="number" id="position" name="position" min="1" max="15"
                           value="<?= htmlspecialchars((string) $position) ?>" placeholder="1-15" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="mission">Mission Type</label>
                    <select id="mission" name="mission" required>
                        <option value="">— Select Mission —</option>
                        <?php foreach (MISSION_TYPES as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>"
                            <?= $mission === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="dispatch-btn">🚀 Dispatch Fleet</button>
        </form>
    </div>
</div>

</body>
</html>
