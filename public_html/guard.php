<?php
/**
 * Security Guard for Include-Only Files
 * Add this at the top of files that should not be accessed directly
 */

// Check if file is being accessed directly
if (count(get_included_files()) === 1) {
    http_response_code(403);
    die('Direct access not permitted');
}
