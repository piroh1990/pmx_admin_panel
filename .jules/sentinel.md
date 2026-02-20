## 2024-05-22 - Unsecured Debug Tools Exposure
**Vulnerability:** Debug and utility scripts (`debug_api.php`, `find_node.php`) were exposing sensitive configuration and allowing unauthenticated API calls to Proxmox.
**Learning:** Developers often create "temporary" utility scripts that bypass authentication for convenience, but these scripts end up deployed to production, creating a massive security hole. The assumption that "nobody knows the URL" is false (security through obscurity).
**Prevention:** All PHP files accessible via web server MUST have authentication checks (`requireAuth()`), regardless of their purpose. Utility scripts should ideally be CLI-only or removed before deployment.
