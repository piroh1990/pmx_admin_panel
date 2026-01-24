# Proxmox VM Admin Panel

A secure, lightweight PHP-based admin panel for managing Proxmox VMs without a database.

## Features

- 🔐 Secure session-based authentication
- 👥 Multi-user support with hashed passwords
- 🛡️ CSRF protection
- ⏱️ Session timeout and fingerprinting
- 🚫 Rate limiting on login attempts
- 🖥️ Simple VM control interface (start, stop, reboot, reset)
- 📊 Real-time VM status monitoring (CPU, memory, uptime)
- 🎯 Per-user VM access control
- 🔄 Auto-refreshing status every 5 seconds

## Installation

1. **Clone the repository**
   ```bash
   git clone <your-repo-url>
   cd pmx_admin_panel
   ```

2. **Configure the application**
   ```bash
   cp config.example.php config.php
   ```

3. **Edit `config.php`** with your settings:
   - Proxmox host and node information
   - API token credentials
   - VM list you want to manage (only these VMs will be accessible)
   - User accounts (see below)

## VM Access Control

### Global VM List
The `$VMS` array in `config.php` defines all available VMs in the system:

```php
$VMS = [
    101 => 'Web Server',
    102 => 'Database',
    103 => 'Backup Server',
];
```

### Per-User Access Control
Each user can have specific VM access defined using the `vm_access` field:

```php
$USERS = [
    'admin' => [
        'password_hash' => '$2y$10$...',
        'name' => 'Administrator',
        'vm_access' => [101, 102, 103], // Access to all VMs
    ],
    'developer' => [
        'password_hash' => '$2y$10$...',
        'name' => 'Developer',
        'vm_access' => [101], // Only access to Web Server
    ],
    'dbadmin' => [
        'password_hash' => '$2y$10$...',
        'name' => 'Database Admin',
        'vm_access' => [102], // Only access to Database
    ],
];
```

**How it works:**
- Users only see and control VMs listed in their `vm_access` array
- VM IDs must also exist in the global `$VMS` array
- If `vm_access` is not defined for a user, they get access to all VMs (backward compatibility)
- Access is enforced at all levels: display, status, and actions

**Security:**
- Users cannot view status of VMs they don't have access to
- Users cannot perform actions on VMs outside their access list
- All API endpoints validate VM access before executing commands

4. **Deploy to your web server**
   - Make sure PHP is installed (PHP 7.4+ recommended)
   - Point your web server to the project directory
   - Ensure proper file permissions

## User Management

### Adding Users

Users are stored in the `$USERS` array in `config.php`. Each user needs a hashed password.

**Generate a password hash:**
```bash
php -r "echo password_hash('your_password_here', PASSWORD_DEFAULT) . PHP_EOL;"
```

**Add to config.php:**
```php
$USERS = [
    'admin' => [
        'password_hash' => '$2y$10$...',  // Your generated hash
        'name' => 'Administrator',
    ],
    'john' => [
        'password_hash' => '$2y$10$...',  // Another hash
        'name' => 'John Doe',
    ],
];
```

### Default Credentials

The example config comes with:
- **Username:** `admin`
- **Password:** `password`

**⚠️ CHANGE THIS IMMEDIATELY in production!**

## Security Features

1. **Password Hashing:** Uses PHP's `password_hash()` with bcrypt
2. **Session Security:**
   - HTTP-only cookies
   - Session fingerprinting (User-Agent validation)
   - Automatic session regeneration
   - Configurable timeout (default: 1 hour)
3. **CSRF Protection:** All state-changing requests require a valid CSRF token
4. **Rate Limiting:** 5 failed login attempts per 15 minutes
5. **Secure Headers:** SameSite cookie policy

## Configuration Options

### Session Settings (config.php)

```php
define('SESSION_NAME', 'pmx_admin_session');  // Session cookie name
define('SESSION_LIFETIME', 3600);              // Session timeout in seconds (1 hour)
```

### SSL Verification

```php
define('VERIFY_SSL', false);  // Set to true in production with valid SSL cert
```

## File Structure

```
pmx_admin_panel/
├── index.php           # Login page
├── admin.php           # Main VM management dashboard
├── logout.php          # Logout handler
├── actions.php         # API endpoint for VM actions
├── status.php          # API endpoint for VM status
├── auth.php            # Authentication helper functions
├── proxmox_api.php     # Proxmox API integration
├── config.php          # Your configuration (not in git)
├── config.example.php  # Configuration template
├── generate_password.php  # Web-based password hash generator
└── .gitignore          # Prevents config.php from being committed
```

## Usage

1. Navigate to your installation URL
2. Log in with your credentials
3. View real-time status of all your VMs:
   - **Green indicator:** VM is running
   - **Red indicator:** VM is stopped
   - **CPU, Memory, Uptime:** Displayed for running VMs
4. Use the dashboard to manage VMs:
   - **Start:** Power on a VM
   - **Shutdown:** Graceful shutdown
   - **Reboot:** Graceful reboot
   - **Reset:** Hard reset (force restart)
5. Status auto-refreshes every 5 seconds

## VM Status Information

For running VMs, the dashboard displays:
- **Status:** Running/Stopped with color-coded indicator
- **CPU Usage:** Current CPU utilization percentage
- **Memory Usage:** Current memory used vs. total allocated
- **Uptime:** How long the VM has been running

## Development

### Requirements
- PHP 7.4 or higher
- Access to Proxmox API
- Valid API token

### Best Practices
- Always use HTTPS in production
- Regularly rotate API tokens
- Use strong passwords for all accounts
- Keep session timeout reasonable for your use case
- Monitor failed login attempts

## Troubleshooting

### "Too many failed attempts"
Wait 15 minutes or clear your session cookie.

### Session expires too quickly
Increase `SESSION_LIFETIME` in `config.php`.

### CSRF token errors
Make sure cookies are enabled and you're not blocking JavaScript.

### Can't connect to Proxmox
- Verify `PVE_HOST` is correct
- Check API token credentials
- Ensure firewall allows connection
- Set `VERIFY_SSL` to `false` if using self-signed cert

## Security Considerations

⚠️ **Important Security Notes:**

1. **Use HTTPS:** Always use HTTPS in production to protect credentials
2. **Secure config.php:** Ensure proper file permissions (600 or 640)
3. **Change default passwords:** Never use example passwords in production
4. **Regular updates:** Keep PHP updated for security patches
5. **Audit logs:** Consider adding logging for security events

## License

MIT License - feel free to modify and use as needed.
