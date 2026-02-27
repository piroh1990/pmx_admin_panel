<?php
/**
 * Authentication helper functions
 * Secure session-based authentication
 */

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === 'auth.php') {
    http_response_code(403);
    die('Direct access not permitted');
}

require_once __DIR__ . '/config.php';

// Start session with secure settings
function startSecureSession() {
    // Set security headers
    if (!headers_sent()) {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; frame-ancestors 'self'; form-action 'self';");
    }

    if (session_status() === PHP_SESSION_NONE) {
        // Secure session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        ini_set('session.cookie_samesite', 'Strict');
        
        session_name(SESSION_NAME);
        session_start();
        
        // Regenerate session ID periodically to prevent session fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            // Regenerate session every 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            // Session expired
            session_unset();
            session_destroy();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
    }
    return true;
}

// Check if user is logged in
function isLoggedIn() {
    global $USERS;
    
    if (!isset($_SESSION['user']) || !isset($_SESSION['authenticated'])) {
        return false;
    }
    
    // Verify user still exists in config
    if (!isset($USERS[$_SESSION['user']])) {
        return false;
    }
    
    // Verify session fingerprint to prevent session hijacking
    $expectedFingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    if (!isset($_SESSION['fingerprint']) || $_SESSION['fingerprint'] !== $expectedFingerprint) {
        return false;
    }
    
    return true;
}

// Require authentication or redirect to login
function requireAuth() {
    startSecureSession();
    
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

// Attempt to log in a user
function attemptLogin($username, $password) {
    global $USERS;
    
    // Rate limiting - simple approach
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt'] = 0;
    }
    
    // Reset attempts after 15 minutes
    if (time() - $_SESSION['last_attempt'] > 900) {
        $_SESSION['login_attempts'] = 0;
    }
    
    // Max 5 attempts
    if ($_SESSION['login_attempts'] >= 5) {
        return [
            'success' => false,
            'message' => 'Too many failed attempts. Please try again in 15 minutes.'
        ];
    }
    
    $_SESSION['last_attempt'] = time();
    
    // Check if user exists
    $userExists = isset($USERS[$username]);

    // Use a dummy hash if user doesn't exist to prevent timing attacks (user enumeration)
    // This hash corresponds to "password"
    $hash = $userExists ? $USERS[$username]['password_hash'] : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    // Always verify password to mitigate timing attacks
    $passwordValid = password_verify($password, $hash);

    // Check if user exists and password is correct
    if ($userExists && $passwordValid) {
        
        // Successful login
        $_SESSION['login_attempts'] = 0;
        $_SESSION['user'] = $username;
        $_SESSION['name'] = $USERS[$username]['name'];
        $_SESSION['authenticated'] = true;
        $_SESSION['fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
        $_SESSION['created'] = time();
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        return [
            'success' => true,
            'message' => 'Login successful'
        ];
    }
    
    // Failed login
    $_SESSION['login_attempts']++;
    
    return [
        'success' => false,
        'message' => 'Invalid username or password'
    ];
}

// Log out the current user
function logout() {
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

// Get current user info
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'username' => $_SESSION['user'],
        'name' => $_SESSION['name']
    ];
}

// Get VMs accessible to current user
function getUserVMs() {
    global $USERS, $VMS;
    
    if (!isLoggedIn()) {
        return [];
    }
    
    $username = $_SESSION['user'];
    
    // If user has specific vm_access defined, use it
    if (isset($USERS[$username]['vm_access']) && is_array($USERS[$username]['vm_access'])) {
        $accessibleVMIds = $USERS[$username]['vm_access'];
        
        // Filter $VMS to only include VMs the user has access to
        $userVMs = [];
        foreach ($accessibleVMIds as $vmid) {
            if (isset($VMS[$vmid])) {
                $userVMs[$vmid] = $VMS[$vmid];
            }
        }
        return $userVMs;
    }
    
    // If no vm_access defined, user has access to all VMs (backward compatibility)
    return $VMS;
}

// Check if user has access to a specific VM
function canAccessVM($vmid) {
    $userVMs = getUserVMs();
    return isset($userVMs[$vmid]);
}

// Generate CSRF token
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
