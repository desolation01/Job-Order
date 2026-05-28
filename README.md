# Job Order System

A PHP + MySQL job order management system with role-based access:
- **Admin** dashboard for reviewing, updating status, history, and imports
- **User** dashboard for creating, filtering, tracking, and exporting job orders

Designed to run locally on **XAMPP (Windows)** with clean URLs (no `.php` in browser paths).

## Features

- Role-based login (`admin`, `user`)
- Job order creation and management
- Status workflow (`Pending`, `Approved`, `Denied`, `Archived`)
- Urgency levels (`Low`, `Medium`, `High`, `Critical`)
- Version/history tracking for edits
- PDF export/download
- Image import + OCR draft review flow
- Clean URLs via Apache rewrite rules

## Tech Stack

- PHP (PDO, sessions, password hashing)
- MySQL / MariaDB
- Apache (mod_rewrite)
- Vanilla CSS/JS

---

## Dependencies

You need:
1. **XAMPP for Windows** (Apache + MySQL + PHP)
2. **PowerShell** (already on Windows)

Recommended:
- PHP 8.1+ (XAMPP recent builds are fine)

---

## Quick Setup (Windows PowerShell)

### 1) Place project in XAMPP htdocs

From PowerShell (run as your normal user):

```powershell
# Example: copy project into htdocs
Copy-Item "C:\path\to\job-order-system" "C:\xampp\htdocs\" -Recurse -Force
```

You should end up with:

`C:\xampp\htdocs\job-order-system`

### 2) Start Apache and MySQL

Start both in XAMPP Control Panel.

### 3) Create database schema and seed data

Option A (phpMyAdmin):
1. Open `http://localhost/phpmyadmin`
2. Import `config/schema.sql`

Option B (PowerShell + mysql CLI):

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "source C:/xampp/htdocs/job-order-system/config/schema.sql"
```

### 4) Open the app

```text
http://localhost/job-order-system/
```

---

## Default Database Configuration

The app reads DB config from environment variables or falls back to:

- `DB_HOST=127.0.0.1`
- `DB_NAME=job_order_system`
- `DB_USER=root`
- `DB_PASS=` (empty)

Source: `config/database.php`

If needed in PowerShell (current shell only):

```powershell
$env:DB_HOST="127.0.0.1"
$env:DB_NAME="job_order_system"
$env:DB_USER="root"
$env:DB_PASS=""
```

---

## Clean URLs (No .php)

This repo includes a root `.htaccess` that:
- redirects `/path.php` -> `/path`
- rewrites `/path` -> `/path.php`

If clean URLs do not work:
1. Ensure Apache `mod_rewrite` is enabled
2. Ensure your Apache config allows overrides for htdocs/project directory:
   - `AllowOverride All`
3. Restart Apache

---

## Auth / Seed Users

`config/schema.sql` seeds two accounts:
- `admin@example.com` (admin)
- `user@example.com` (user)

If you do not know the plaintext password for your local copy, reset it quickly:

```sql
UPDATE users
SET password = '$2y$10$BK9KgHdt8YoXZUVPg.VSDuh1gZ5GlUEPRXHTjmzVd9nGX7LZdBlOy'
WHERE email IN ('admin@example.com', 'user@example.com');
```

Or register a new user from `/auth/register`.

---

## Project Structure

```text
admin/      # admin pages (dashboard, edit, import, history, status actions)
user/       # user pages (dashboard, create, import, view, history)
auth/       # login/register/logout
config/     # DB config + SQL schema
includes/   # shared functions, layout, repository, form partials, PDF helpers
assets/     # css/js/images/uploads
tests/      # static verification script(s)
index.php   # entry point (redirects by auth/role)
```

---

## Run Static Checks

From project root:

```powershell
powershell -ExecutionPolicy Bypass -File .\tests\run-static-checks.ps1
```

---

## Common Local Troubleshooting

### 1) `Database connection failed`
- Confirm MySQL is running
- Confirm `job_order_system` exists
- Check DB credentials in environment or `config/database.php`

### 2) `404` on clean URLs
- Check `.htaccess` exists in project root
- Enable `mod_rewrite`
- Set `AllowOverride All`
- Restart Apache

### 3) Uploaded image issues
- Ensure `assets/uploads/job-order-images/` exists and is writable

### 4) Access denied pages
- Admin-only routes require an admin account/session

---

## Security / Repo Notes

- Keep local-only or private docs (e.g. `CODEX.md`) out of public repos
- Use `.gitignore` for secrets and local artifacts
- Never commit real production credentials

---

## License

Add your preferred license file (for example MIT) before publishing publicly.
