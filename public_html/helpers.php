<?php
/**
 * Reusable helper functions for the admin panel.
 * Include-only file — do not access directly.
 */

// Prevent direct access
if (count(get_included_files()) === 1) {
    http_response_code(403);
    die('Direct access not permitted');
}

/**
 * Valid mission types used throughout the application.
 */
define('MISSION_TYPES', [
    'attack'     => 'Attack',
    'transport'  => 'Transport',
    'deploy'     => 'Deploy',
    'espionage'  => 'Espionage',
    'colonize'   => 'Colonize',
    'harvest'    => 'Harvest',
    'expedition' => 'Expedition',
    'acs_attack' => 'ACS Attack',
]);

/**
 * Generate a clickable coordinate link that redirects to the Fleet Dispatch
 * page with the coordinates (and optional mission type) pre-filled.
 *
 * @param int    $galaxy   Galaxy number.
 * @param int    $system   System number.
 * @param int    $position Position number.
 * @param string $mission  Optional mission key (one of MISSION_TYPES keys).
 *
 * @return string HTML anchor tag, e.g.
 *   <a href="fleet.php?galaxy=1&amp;system=448&amp;position=13" class="coord-link">[1:448:13]</a>
 */
function coordinateLink(int $galaxy, int $system, int $position, string $mission = ''): string
{
    $params = [
        'galaxy'   => $galaxy,
        'system'   => $system,
        'position' => $position,
    ];

    if ($mission !== '' && isset(MISSION_TYPES[$mission])) {
        $params['mission'] = $mission;
    }

    $url  = 'fleet.php?' . http_build_query($params);
    $text = htmlspecialchars("[{$galaxy}:{$system}:{$position}]", ENT_QUOTES, 'UTF-8');

    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="coord-link" title="Open Fleet Dispatch">' . $text . '</a>';
}
