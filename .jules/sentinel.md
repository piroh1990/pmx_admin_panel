## 2024-05-23 - Unauthenticated Debug Endpoint
**Vulnerability:** Found `debug_api.php` which exposed sensitive configuration (API tokens, host details) without requiring authentication.
**Learning:** Utility/debug scripts often bypass standard security controls (like `requireAuth()`) because developers treat them as temporary or local-only tools, leading to critical information disclosure.
**Prevention:** Enforce a "secure by default" policy where ALL PHP files (except public entry points like login) must call `requireAuth()` at the top. Use web server config to block access to `debug_*` files in production as a second layer of defense.

## 2024-05-24 - Exception Information Leakage
**Vulnerability:** Found `status.php`, `actions.php`, and `proxmox_api.php` directly echoing exception messages, file paths, and line numbers in JSON responses.
**Learning:** Raw PHP exception handling without a framework often leads to developers exposing internal details (like `getMessage()` and `getFile()`) for debugging convenience, which persists into production and aids attackers in reconnaissance.
**Prevention:** Implement a global exception handler or ensure all API endpoints catch exceptions, log the full details server-side using `error_log()`, and return only generic, sanitized error messages to the client.
