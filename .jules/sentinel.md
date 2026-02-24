# Sentinel's Journal

## 2024-05-22 - Unsecured Debug Tools Leak Secrets
**Vulnerability:** Found `debug_api.php` and `find_node.php` accessible without authentication, exposing `PVE_TOKEN_SECRET` and internal network structure.
**Learning:** Utility scripts often bypass standard security checks because they are "for testing only", but if deployed to production, they become critical vulnerabilities.
**Prevention:** Always enforce authentication on ALL PHP files that handle sensitive data, even if they are "debug" tools. Use `requireAuth()` consistently.

## 2026-02-24 - User Enumeration via Timing Attack
**Vulnerability:** The authentication logic in `auth.php` allowed user enumeration by skipping `password_verify` for non-existent users, creating a measurable timing difference.
**Learning:** Checking for user existence before verifying credentials can leak information about which users are valid. Security checks should be constant-time where possible.
**Prevention:** Always perform the expensive cryptographic operations (like `password_verify`) regardless of whether the user exists, using a dummy hash if necessary.
