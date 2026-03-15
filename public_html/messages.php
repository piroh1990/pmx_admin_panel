<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

// Require authentication
requireAuth();

$user      = getCurrentUser();
$csrfToken = generateCsrfToken();

// ── Sample messages / reports ────────────────────────────────────────────────
// In a real application these would come from a database or API.
$messages = [
    [
        'id'        => 1,
        'type'      => 'combat',
        'subject'   => 'Combat Report: Attack on Planet',
        'date'      => '2026-03-15 08:32',
        'content'   => 'Your fleet attacked the planet at the coordinates shown below. You won the battle and captured 12 500 Metal, 8 000 Crystal, and 3 200 Deuterium.',
        'coords'    => ['galaxy' => 1, 'system' => 448, 'position' => 13],
        'suggested_mission' => 'attack',
    ],
    [
        'id'        => 2,
        'type'      => 'espionage',
        'subject'   => 'Espionage Report',
        'date'      => '2026-03-15 07:10',
        'content'   => 'Your espionage probe has returned data from the target. Resources found: 45 000 Metal, 32 000 Crystal, 11 000 Deuterium. Fleet power: 1 200.',
        'coords'    => ['galaxy' => 2, 'system' => 55, 'position' => 7],
        'suggested_mission' => 'attack',
    ],
    [
        'id'        => 3,
        'type'      => 'transport',
        'subject'   => 'Transport Receipt',
        'date'      => '2026-03-14 22:45',
        'content'   => 'Your transport fleet has successfully delivered 20 000 Metal and 15 000 Crystal to your colony.',
        'coords'    => ['galaxy' => 3, 'system' => 210, 'position' => 4],
        'suggested_mission' => 'transport',
    ],
    [
        'id'        => 4,
        'type'      => 'espionage',
        'subject'   => 'Espionage Report',
        'date'      => '2026-03-14 18:20',
        'content'   => 'Your espionage probe gathered intelligence from the target. Resources found: 120 000 Metal, 90 000 Crystal, 40 000 Deuterium. No fleet detected.',
        'coords'    => ['galaxy' => 4, 'system' => 100, 'position' => 1],
        'suggested_mission' => 'attack',
    ],
    [
        'id'        => 5,
        'type'      => 'combat',
        'subject'   => 'Combat Report: Defense',
        'date'      => '2026-03-14 14:55',
        'content'   => 'An enemy fleet attacked your colony. You successfully defended the planet. No resources were lost.',
        'coords'    => ['galaxy' => 1, 'system' => 300, 'position' => 8],
        'suggested_mission' => 'espionage',
    ],
    [
        'id'        => 6,
        'type'      => 'expedition',
        'subject'   => 'Expedition Log',
        'date'      => '2026-03-14 10:30',
        'content'   => 'Your expedition fleet has discovered an ancient debris field in deep space. Recovered 5 000 Metal and 2 000 Crystal.',
        'coords'    => ['galaxy' => 5, 'system' => 1, 'position' => 12],
        'suggested_mission' => 'expedition',
    ],
];

// Icon map per report type
$typeIcons = [
    'combat'     => '⚔️',
    'espionage'  => '🔍',
    'transport'  => '📦',
    'expedition' => '🧭',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages - Proxmox VM Admin</title>
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

.message-card {
    background: #1e1e1e;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    border: 1px solid #333;
}

.message-card.combat     { border-left: 4px solid #f44; }
.message-card.espionage  { border-left: 4px solid #f90; }
.message-card.transport  { border-left: 4px solid #2d7; }
.message-card.expedition { border-left: 4px solid #a6f; }

.msg-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.msg-subject {
    font-size: 16px;
    font-weight: 600;
}

.msg-date {
    font-size: 12px;
    color: #888;
}

.msg-body {
    font-size: 14px;
    color: #ccc;
    line-height: 1.6;
    margin-bottom: 12px;
}

.msg-footer {
    font-size: 13px;
    color: #aaa;
    display: flex;
    align-items: center;
    gap: 6px;
}

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
    <h1>📨 Messages &amp; Reports</h1>
    <div class="nav-links">
        <a href="admin.php">Dashboard</a>
        <a href="fleet.php">Fleet Dispatch</a>
        <a href="missions.php">Missions</a>
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
    <?php foreach ($messages as $msg): ?>
    <div class="message-card <?= htmlspecialchars($msg['type']) ?>">
        <div class="msg-header">
            <span class="msg-subject">
                <?= $typeIcons[$msg['type']] ?? '📩' ?>
                <?= htmlspecialchars($msg['subject']) ?>
            </span>
            <span class="msg-date"><?= htmlspecialchars($msg['date']) ?></span>
        </div>

        <div class="msg-body">
            <?= htmlspecialchars($msg['content']) ?>
        </div>

        <div class="msg-footer">
            📍 Coordinates:
            <?= coordinateLink(
                $msg['coords']['galaxy'],
                $msg['coords']['system'],
                $msg['coords']['position'],
                $msg['suggested_mission']   // ← contextual mission type
            ) ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

</body>
</html>
