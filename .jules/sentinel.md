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
