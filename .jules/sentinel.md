## 2024-05-23 - Unauthenticated Debug Endpoint
**Vulnerability:** Found `debug_api.php` which exposed sensitive configuration (API tokens, host details) without requiring authentication.
**Learning:** Utility/debug scripts often bypass standard security controls (like `requireAuth()`) because developers treat them as temporary or local-only tools, leading to critical information disclosure.
**Prevention:** Enforce a "secure by default" policy where ALL PHP files (except public entry points like login) must call `requireAuth()` at the top. Use web server config to block access to `debug_*` files in production as a second layer of defense.
