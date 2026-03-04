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
