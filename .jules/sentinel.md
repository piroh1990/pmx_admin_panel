# Sentinel's Journal

## 2024-05-22 - Unsecured Debug Tools Leak Secrets
**Vulnerability:** Found `debug_api.php` and `find_node.php` accessible without authentication, exposing `PVE_TOKEN_SECRET` and internal network structure.
**Learning:** Utility scripts often bypass standard security checks because they are "for testing only", but if deployed to production, they become critical vulnerabilities.
**Prevention:** Always enforce authentication on ALL PHP files that handle sensitive data, even if they are "debug" tools. Use `requireAuth()` consistently.

## 2024-05-22 - Login Timing Attack
**Vulnerability:** Found a timing attack in `attemptLogin()` where `password_verify()` was skipped if the user didn't exist, allowing username enumeration.
**Learning:** Checking for user existence before verifying passwords creates a measurable timing difference (0ms vs ~70ms).
**Prevention:** Always perform `password_verify()` with a valid hash (dummy or real) to ensure constant-time login attempts.
