<!-- Copilot instructions for AI coding agents working on this PHP + Google Drive repo -->
# Project-specific guidance for AI assistants

This repository is a small PHP website (news + downloads) that integrates with Google Drive. Use the notes below to be immediately productive and avoid making unsafe or irrelevant changes.

- **Big picture**: The app serves a public front-end (`index.php`, `news.php`, `news-detail.php`, `downloads.php`) and a basic admin area (`admin/`) that authenticates against an `admins` DB table. File meta (for downloads) is stored in `documents` and linked to Google Drive via `drive_id` / `drive_url`.

- **Config & DB**: `includes/config.php` centralizes configuration and exposes `get_db()` (returns a PDO instance). The project supports two DB backends: `DB_TYPE === 'mysql'` (default here) or `sqlite`. When modifying DB access, use `get_db()` and prepared statements.

- **Key helper functions**: Use the helpers in `includes/`:
  - `get_db()` — open DB connection (MySQL or SQLite)
  - `e($str)` — HTML-escape output
  - `format_datetime()` — consistent time formatting
  - `get_google_client()` (in `includes/google.php`) — returns configured `Google_Client` (prefers OAuth token if present, falls back to service account)
  - `drive_log($msg)` — writes logs to `credentials/drive_upload.log` when `GOOGLE_DRIVE_DEBUG` is enabled

- **Google Drive integration**:
  - OAuth token path: `credentials/oauth_token.json` (optional)
  - Service account path: `credentials/service-account.json` (used as fallback)
  - Constants in `includes/config.php`: `GOOGLE_DRIVE_FOLDER_ID`, `GOOGLE_SERVICE_ACCOUNT_PATH`, and env variables `GOOGLE_OAUTH_CLIENT_ID`, `GOOGLE_OAUTH_CLIENT_SECRET`, `GOOGLE_OAUTH_REDIRECT`
  - Prefer calling `get_google_client()` and then `Google_Service_Drive` methods; follow the patterns in `includes/google.php` for token refresh and Guzzle handler fallbacks.

- **Admin flow**: The admin UI uses PHP sessions. Successful login sets `$_SESSION['admin_logged'] = true` and `$_SESSION['admin_id']`. Protect admin endpoints by checking `session_start()` and `$_SESSION['admin_logged']`.

- **Conventions & patterns**:
  - Keep DB interactions via `get_db()` and PDO prepared statements.
  - Use `includes/header.php` / `includes/footer.php` for HTML wrapper and Tailwind CDN is used for styling.
  - Files under `admin/` are simple PHP pages (no MVC framework); add server-side validation and session checks when modifying them.

- **Local dev & commands**:
  - Environment: XAMPP on Windows (project is typically placed under `C:\xampp\htdocs\`). Start `Apache` and `MySQL` from XAMPP Control Panel.
  - Import DB schema: use `mysql_schema.sql` via phpMyAdmin.
  - Create admin password hash via PHP CLI (PowerShell example):

```powershell
php -r "echo password_hash('your-password', PASSWORD_DEFAULT).PHP_EOL;"
```

  - If you need Google client libraries: `composer require google/apiclient:^2.0`

- **When editing Drive upload code**:
  - Reuse `get_google_client()`; prefer OAuth flow when `credentials/oauth_token.json` exists, otherwise service account is used.
  - Respect `GOOGLE_DRIVE_FOLDER_ID` for uploaded file parent folder.
  - Use `drive_log()` for debug traces; do not enable `GOOGLE_DRIVE_DEBUG` true in production commits.

- **Security & secrets**:
  - Do NOT commit new secrets. This repo currently contains some credential files under `credentials/` — treat them as sensitive. If adding or changing credentials, store them outside the repository or add to .gitignore.
  - The codebase sets some OAuth env vars in `includes/config.php` via `putenv()` — be cautious when changing these.

- **Tests & verification**:
  - There are no automated tests; verify changes locally with XAMPP.
  - To validate Drive uploads locally, either:
    - Use an OAuth flow (admin/oauth_connect.php + admin/oauth_callback.php) and ensure `credentials/oauth_token.json` is populated, or
    - Provide a service-account JSON and ensure `credentials/service-account.json` is available (not committed for public repos).

- **Files worth reading before making changes**:
  - `includes/config.php` — environment, DB and Drive constants
  - `includes/google.php` — Drive client and logging logic
  - `includes/header.php`, `includes/footer.php` — UI shell and common assets
  - `admin/login.php`, `admin/dashboard.php` — admin auth and session patterns
  - `mysql_schema.sql` — DB schema for `news`, `documents`, `admins`
  - `README.md` — setup notes (Thai language) and examples

If anything in these notes is unclear or you need more examples (for example a code snippet that uploads a file using the repo's helpers), say which area and I will expand with precise, ready-to-insert code.
