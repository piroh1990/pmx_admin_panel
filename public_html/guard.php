<?php
/**
 * Security Guard for Include-Only Files
 * Add this at the top of files that should not be accessed directly
 */

// Check if file is being accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    die('Direct access not permitted');
}
