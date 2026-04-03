# Sentinel's Journal

## 2024-05-22 - Unsecured Debug Tools Leak Secrets
**Vulnerability:** Found `debug_api.php` and `find_node.php` accessible without authentication, exposing `PVE_TOKEN_SECRET` and internal network structure.
**Learning:** Utility scripts often bypass standard security checks because they are "for testing only", but if deployed to production, they become critical vulnerabilities.
**Prevention:** Always enforce authentication on ALL PHP files that handle sensitive data, even if they are "debug" tools. Use `requireAuth()` consistently.

## 2024-05-23 - User Enumeration via Timing Attacks
**Vulnerability:** `attemptLogin` in `auth.php` short-circuited `password_verify`, causing a timing leak that allowed user enumeration.
**Learning:** `password_verify` is intentionally slow. If it's skipped for invalid users, an attacker can distinguish valid vs invalid usernames by response time (70ms vs <1ms).
**Prevention:** Always ensure `password_verify` executes with a valid hash (dummy if needed) for every login attempt, regardless of whether the user exists.

## 2024-05-24 - Input Type Mismatch DoS and Errors
**Vulnerability:** `attemptLogin` in `auth.php` lacked explicit type checking for `$username` and `$password`, making it vulnerable to TypeErrors when arrays were passed via `$_POST` (e.g., `username[]=admin`).
**Learning:** Raw PHP arrays passed to string-only functions (like `password_verify` or array key accesses) cause fatal TypeErrors. Unhandled errors can crash the PHP process for a request, leak internals if error reporting is on, or potentially lead to DoS.
**Prevention:** Always validate user input types using `is_string()` before processing credentials or passing input to functions that strictly expect strings.

## 2024-05-25 - CSRF Token Leak in URLs
**Vulnerability:** `admin.php` and `check_status.php` passed the CSRF token to `status.php` via a GET parameter in the URL.
**Learning:** Passing security tokens or session identifiers in the URL query string exposes them to server logs, proxy logs, browser history, and the `Referer` header.
**Prevention:** For GET requests that require CSRF protection, pass the token securely using a custom HTTP header (like `X-CSRF-Token`).

## 2024-05-26 - Rate Limiting Bypass via Session Dropping
**Vulnerability:** The login rate limiting in `auth.php` was tracking failed attempts using `$_SESSION`. An attacker could bypass the 5-attempt limit completely by simply clearing their session cookie on every request, allowing infinite brute-force attacks on the login endpoint.
**Learning:** Storing security state (like rate limit counters) in a client-controlled mechanism like a session cookie makes the protection trivial to bypass. If an attacker can drop the cookie, they drop the restriction.
**Prevention:** Always track authentication rate limiting server-side, tied to an identifier the client cannot easily change or omit, such as the IP address (`$_SERVER['REMOTE_ADDR']`).

## 2026-03-06 - Direct Access Bypass via PATH_INFO
**Vulnerability:** Include-only files (`auth.php`, `proxmox_api.php`, `guard.php`) used `basename($_SERVER['PHP_SELF'])` to check if they were accessed directly. An attacker could bypass this by appending `/foo` to the URL (e.g., `/auth.php/foo`), making `basename` return `foo` instead of the filename, causing the security check to fail and executing the file directly.
**Learning:** `$_SERVER['PHP_SELF']` includes PATH_INFO, meaning it can be manipulated by the client appending paths to an existing file endpoint. Relying on `basename($_SERVER['PHP_SELF'])` for access control is insecure and easily bypassed.
**Prevention:** Use `count(get_included_files()) === 1` to reliably determine if a PHP script is the main entry point (being accessed directly). This method is immune to PATH_INFO manipulation.

## 2024-05-27 - Missing Timeout on External API Calls
**Vulnerability:** External API calls to the Proxmox server via cURL (`proxmoxRequest`) did not have a timeout configured. If the Proxmox server became unresponsive or unreachable, the PHP process would hang indefinitely, potentially leading to resource exhaustion and a Denial of Service (DoS) condition.
**Learning:** Default cURL configurations in PHP do not enforce a strict timeout. Relying on default timeouts can cause applications to hang and consume server resources (like PHP-FPM workers) when interacting with unstable external services.
**Prevention:** Always explicitly set `CURLOPT_TIMEOUT` (and optionally `CURLOPT_CONNECTTIMEOUT`) for all external HTTP requests to ensure they fail fast and fail securely.

## 2024-05-28 - Insecure Direct Object Reference (IDOR) in Debug Tools
**Vulnerability:** `debug_api.php` accessed the global `$VMS` array to iterate over and test all VM statuses, completely bypassing the authorization checks defined in `getUserVMs()` for the logged-in user.
**Learning:** Diagnostic and debug tools often bypass standard access-control abstractions in an attempt to provide complete visibility. When these tools are accessible in production, they can expose sensitive data or functionality across security boundaries (e.g., exposing one tenant's VMs to another tenant).
**Prevention:** Debug endpoints must respect the same role-based access control (RBAC) and authorization constraints as the primary application features unless explicitly restricted to super-administrators. Always use data-access functions (`getUserVMs()`) instead of raw global state (`global $VMS`).

## 2024-05-29 - Cross-Site Scripting (XSS) Vulnerabilities
**Vulnerability:** Found both DOM-based XSS in `check_status.php` (unsanitized API response injected via `innerHTML`) and Reflected XSS in `find_node.php` (unsanitized node names and errors echoed directly into HTML output).
**Learning:** Even internal diagnostic tools need strict output encoding. `innerHTML` should be avoided for rendering data from external APIs, and raw `echo` statements in PHP are dangerous if the input isn't entirely trusted or static.
**Prevention:** Always use `document.createTextNode()` or `textContent` for dynamic DOM manipulation in JavaScript. In PHP, consistently apply `htmlspecialchars()` to any variables being output into an HTML context.

## 2026-03-06 - Log Forging (CRLF Injection) in Audit Logs
**Vulnerability:** `auth.php`, `actions.php`, and `status.php` directly logged user-supplied inputs (like `$username`, `$action`, `$ip`) and Exception messages via `error_log()` without sanitization. An attacker could inject newline characters (`\n`, `\r`) into these inputs to forge multi-line log entries, potentially confusing log analysis tools or hiding malicious activity.
**Learning:** Functions like `error_log()` simply write strings as-is. If the string contains newlines from an untrusted source, it creates a new log line, allowing log forging/CRLF injection.
**Prevention:** Always sanitize any variable passed to a logging function by removing or replacing newline characters (e.g., `str_replace(array("\r", "\n", "%0d", "%0a"), ' ', $input)`).

## 2024-05-30 - Insecure Session Cookies & Missing HSTS
**Vulnerability:** The session configuration in `auth.php` hardcoded `ini_set('session.cookie_secure', 0)`, meaning session cookies were always sent unencrypted over HTTP, even when accessed via HTTPS. Furthermore, the `Strict-Transport-Security` (HSTS) header was completely missing.
**Learning:** Hardcoding insecure defaults to simplify local development creates a massive risk in production. If an administrator accesses the panel over public Wi-Fi, an active MITM attacker could intercept the `pmx_admin_session` cookie and hijack their session, gaining full control of the Proxmox VMs.
**Prevention:** Always dynamically detect the connection protocol (checking `$_SERVER['HTTPS']` and `$_SERVER['HTTP_X_FORWARDED_PROTO']`) to enable the `Secure` flag on cookies and enforce HSTS in production without breaking local HTTP-based testing.
## 2026-03-21 - Secret Leakage in Debug Tool
**Vulnerability:** The API debug tool (`debug_api.php`) was intentionally leaking portions (the first 8 and last 4 characters) of the `PVE_TOKEN_SECRET` using `substr()`.
**Learning:** Even internal diagnostic scripts meant to be hidden or restricted should never display partial secrets. Partial secrets can still reduce entropy or act as a stepping stone in an exploit chain if the debug tool is accidentally exposed.
**Prevention:** Always completely redact sensitive configuration values (like API tokens or secrets) when outputting debug information (e.g., using `*** REDACTED ***`).

## 2026-03-26 - Predictable Temporary File for Rate Limiting
**Vulnerability:** The rate limiting file in `auth.php` was created using a predictable path: `sys_get_temp_dir() . '/pmx_login_' . md5($ip) . '.txt'`. In a shared environment (e.g., `/tmp`), an attacker could pre-create this file and restrict write access to it. When the PHP script attempts to log a failed login, `file_put_contents()` would silently fail (due to the `@` error suppression), effectively preventing the failure count from increasing and entirely bypassing the login rate limiter (CWE-377).
**Learning:** Storing security state files in world-writable, shared directories using predictable names allows local attackers to perform Symlink or Denial-of-Service attacks against the security mechanism itself.
**Prevention:** Always use a secret server-side value to salt filenames generated in shared temporary directories. Using `md5($ip . PVE_TOKEN_SECRET)` ensures the filename is unpredictable, preventing an attacker from pre-creating the file.

## 2026-04-03 - Fail-Open Authorization Bypass via Misconfiguration
**Vulnerability:** The `getUserVMs()` function fell back to granting full access (all VMs) if the `vm_access` array was misconfigured (e.g., defined as an integer, string, or null instead of an array). An admin accidentally typing `'vm_access' => 101` instead of `[101]` would unknowingly grant the user full access to all VMs.
**Learning:** Security checks that rely on optional configuration must fail securely. If a security restriction is explicitly declared but invalid, the system should strictly deny access rather than falling back to permissive defaults.
**Prevention:** Always use `array_key_exists` rather than `isset` combined with type checking to detect misconfigurations, and default to the most restrictive state (deny all) when the configuration is invalid.
