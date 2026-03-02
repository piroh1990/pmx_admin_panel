<?php
// ===== Proxmox connection =====
define('PVE_HOST', 'https://proxmox.example.com:8006');
define('PVE_NODE', 'pve');

// API Token
define('PVE_TOKEN_ID', 'api@pve!php');
define('PVE_TOKEN_SECRET', 'PUT_YOUR_SECRET_HERE');

// ===== VM list you want to control =====
// vmid => display name
$VMS = [
    101 => 'Web Server',
    102 => 'Database',
    103 => 'Backup Server',
];

// ===== Security =====
// Users with hashed passwords (use password_hash())
// To generate a password hash, run: php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"
$USERS = [
    'admin' => [
        'password_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password: "password"
        'name' => 'Administrator',
        'vm_access' => [101, 102, 103], // VMs this user can access (all)
    ],
    // Add more users as needed
    // 'username' => [
    //     'password_hash' => 'GENERATE_HASH_HERE',
    //     'name' => 'Full Name',
    //     'vm_access' => [101], // Only specific VMs
    // ],
];

// Session configuration
define('SESSION_NAME', 'pmx_admin_session');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// SSL verification (true recommended if cert is valid)
define('VERIFY_SSL', false);
