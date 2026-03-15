<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

// Require authentication
requireAuth();

$user      = getCurrentUser();
$csrfToken = generateCsrfToken();

// ── Sample mission data ─────────────────────────────────────────────────────
// In a real application these would come from a database or API.
$activeMissions = [
    [
        'id'       => 1,
        'name'     => 'Transport Resources to Colony',
        'type'     => 'transport',
        'target'   => ['galaxy' => 1, 'system' => 448, 'position' => 13],
        'status'   => 'In Progress',
        'eta'      => '12m 34s',
    ],
    [
        'id'       => 2,
        'name'     => 'Espionage Probe',
        'type'     => 'espionage',
        'target'   => ['galaxy' => 2, 'system' => 55,  'position' => 7],
        'status'   => 'In Progress',
        'eta'      => '3m 12s',
    ],
    [
        'id'       => 3,
        'name'     => 'Attack Enemy Planet',
        'type'     => 'attack',
        'target'   => ['galaxy' => 3, 'system' => 210, 'position' => 4],
        'status'   => 'Returning',
        'eta'      => '8m 45s',
    ],
    [
        'id'       => 4,
        'name'     => 'Colonize New World',
        'type'     => 'colonize',
        'target'   => ['galaxy' => 1, 'system' => 300, 'position' => 8],
        'status'   => 'In Progress',
        'eta'      => '25m 10s',
    ],
    [
        'id'       => 5,
        'name'     => 'Harvest Debris Field',
        'type'     => 'harvest',
        'target'   => ['galaxy' => 2, 'system' => 55,  'position' => 7],
        'status'   => 'Queued',
        'eta'      => '—',
    ],
    [
        'id'       => 6,
        'name'     => 'Deploy Fleet to Moon',
        'type'     => 'deploy',
        'target'   => ['galaxy' => 4, 'system' => 100, 'position' => 1],
        'status'   => 'In Progress',
        'eta'      => '45m 00s',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Missions - Proxmox VM Admin</title>
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
    max-width: 1000px;
    margin: 0 auto;
    padding: 30px 20px;
}

.panel {
    background: #1e1e1e;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    border: 1px solid #333;
    margin-bottom: 20px;
}

.panel h2 {
    margin: 0 0 20px;
    font-size: 20px;
    border-bottom: 1px solid #333;
    padding-bottom: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    text-align: left;
    padding: 10px 12px;
    border-bottom: 1px solid #2a2a2a;
}

th {
    color: #888;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
}

td { font-size: 14px; }

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

.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.in-progress {
    background: rgba(0,153,255,0.15);
    color: #4cf;
    border: 1px solid rgba(0,153,255,0.4);
}

.status-badge.returning {
    background: rgba(255,153,0,0.15);
    color: #fc6;
    border: 1px solid rgba(255,153,0,0.4);
}

.status-badge.queued {
    background: rgba(136,136,136,0.15);
    color: #aaa;
    border: 1px solid rgba(136,136,136,0.4);
}

.mission-type {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    background: rgba(255,255,255,0.06);
    color: #ccc;
}
</style>
</head>
<body>

<div class="header">
    <h1>📋 Missions</h1>
    <div class="nav-links">
        <a href="admin.php">Dashboard</a>
        <a href="fleet.php">Fleet Dispatch</a>
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
        <h2>🚀 Active Missions</h2>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Mission</th>
                    <th>Type</th>
                    <th>Target Coordinates</th>
                    <th>Status</th>
                    <th>ETA</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($activeMissions as $m): ?>
                <tr>
                    <td><?= $m['id'] ?></td>
                    <td><?= htmlspecialchars($m['name']) ?></td>
                    <td><span class="mission-type"><?= htmlspecialchars(MISSION_TYPES[$m['type']] ?? $m['type']) ?></span></td>
                    <td>
                        <?= coordinateLink(
                            $m['target']['galaxy'],
                            $m['target']['system'],
                            $m['target']['position'],
                            $m['type']              // ← passes mission type into the link
                        ) ?>
                    </td>
                    <td>
                        <?php
                            $badgeClass = 'queued';
                            if ($m['status'] === 'In Progress') {
                                $badgeClass = 'in-progress';
                            } elseif ($m['status'] === 'Returning') {
                                $badgeClass = 'returning';
                            }
                        ?>
                        <span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($m['status']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($m['eta']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
