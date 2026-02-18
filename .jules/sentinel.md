## 2026-02-18 - Unprotected Debug Tools
**Vulnerability:** Debug tools (debug_api.php, find_node.php) exposed sensitive configuration (API secrets) and allowed unauthenticated API interaction.
**Learning:** Developers often leave debug tools unsecured, assuming they are hidden or protected by obscurity (like specific filenames or not linking to them). Even if Nginx config attempts to block them, the application code must be secure by default.
**Prevention:** Always require authentication in ALL PHP files that perform sensitive actions or access configuration, even if they are intended for internal use only.
