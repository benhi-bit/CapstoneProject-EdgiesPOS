# DEPLOYMENT

This chapter provides instructions for deploying the web-based POS (Point of Sale) system into the production environment. It includes the target deployment environment, step-by-step deployment procedures, and a post-deployment checklist to ensure the system functions correctly.

---

## Deployment Environment

### Target Environment Overview

| Component        | Requirement |
|-----------------|-------------|
| **Web Server**  | Apache 2.4+ or Nginx |
| **PHP**         | PHP 7.4 or higher (8.0+ recommended) |
| **Database**    | MySQL 5.7+ or MariaDB 10.3+ |
| **OS**          | Windows Server, Linux (Ubuntu 20.04+, CentOS 7+), or existing XAMPP/WAMP/LAMP host |

### Required PHP Extensions

- **mysqli** – Database connectivity  
- **gd** – Image processing (product images)  
- **mbstring** – String handling  
- **openssl** – Two-factor authentication (2FA)  
- **json** – JSON encoding/decoding  
- **session** – User sessions  

### Optional (for receipt printing)

- **php-printer** or ESC/POS support (e.g. via mike42/escpos-php)

### Client Requirements

- Modern browser: Chrome, Firefox, Edge, or Safari (latest versions)  
- JavaScript enabled  
- Cookies enabled (for sessions)  

### Network / Access

- Server must be reachable on HTTP/HTTPS (port 80/443).  
- For production, use **HTTPS** and a valid or internal SSL certificate.  
- Database may be on the same host (e.g. `localhost`) or a dedicated DB server; firewall rules must allow the web server to connect to MySQL/MariaDB (default port 3306).

---

## Deployment Steps

### Step 1: Prepare the Server

1. **Install or confirm stack**
   - **Option A (XAMPP/WAMP on Windows):** Install XAMPP/WAMP; start Apache and MySQL from the control panel.  
   - **Option B (Linux):** Install Apache, PHP, and MySQL/MariaDB via package manager (e.g. `apt`, `yum`).  
   - **Option C (Existing host):** Ensure PHP 7.4+ and MySQL/MariaDB are installed and running.

2. **Verify PHP**
   - Run: `php -v`  
   - Confirm version is 7.4 or higher.

3. **Verify PHP extensions**
   - Run: `php -m`  
   - Confirm `mysqli`, `gd`, `mbstring`, `openssl`, `json`, `session` are listed.

### Step 2: Create the Database

1. **Create database**
   - Open phpMyAdmin (e.g. `http://your-server/phpmyadmin`) or MySQL client.  
   - Create a new database, e.g. `edgies_pos` (or your chosen name).  
   - Set collation to `utf8mb4_general_ci` (or `utf8mb4_unicode_ci`) for full Unicode support.

2. **Import schema and initial data**
   - Select the database.  
   - Use **Import** and choose your project’s SQL file (e.g. from `database/` folder, such as `*_edgies_pos_backup.sql` or the provided schema/seed file).  
   - Execute the import and confirm no errors.

3. **Database user (production)**
   - Create a dedicated MySQL user for the application (do not use `root` in production).  
   - Grant privileges: `SELECT, INSERT, UPDATE, DELETE` (and others if your scripts require them) on `edgies_pos` (or your DB name).  
   - Note the username and password for Step 4.

### Step 3: Upload Application Files

1. **Copy project files**
   - Copy the entire project folder to the web server document root, e.g.  
     - XAMPP (Windows): `C:\xampp\htdocs\`  
     - WAMP: `C:\wamp64\www\`  
     - Linux: `/var/www/html/`  
   - Ensure folder name has no spaces in production if possible (e.g. `edgies-pos` or `pos`) to avoid URL issues.

2. **Set permissions (Linux)**
   - Directories: `chmod 755` (or `775` if web server needs write access).  
   - Files: `chmod 644`.  
   - Writable directories (if any): e.g. `images/`, `config/` (if app writes there): `chmod 775` and set owner/group to the web server user (e.g. `www-data`).

3. **Exclude sensitive or dev files**
   - Do not expose `.env`, `.git`, or local/dev config files to the web root if present.

### Step 4: Configure the Application

1. **Database configuration**
   - Open `config/connection.php`.  
   - Set:
     - `$servername` – database host (e.g. `localhost` or DB server IP/hostname).  
     - `$username` – database user.  
     - `$password` – database password.  
     - `$dbname` – database name (e.g. `edgies_pos`).  
   - Save the file.

2. **Other config files**
   - If the project uses `config/backup_schedule.json` or similar, ensure the path is correct and the web server can read (and optionally write) it.  
   - Ensure any hardcoded paths (e.g. in `backup_database.php` or `tools/scheduled_backup.php`) match the deployed path.

3. **PHP settings (optional but recommended)**
   - In `php.ini`: set `display_errors = Off` and `log_errors = On` for production.  
   - Adjust `upload_max_filesize` and `post_max_size` if you upload large backup files or images.

### Step 5: Install Dependencies

1. **Composer**
   - If the project uses Composer, open a terminal in the project root and run:
     - `composer install --no-dev`  
   - This installs production dependencies (e.g. escpos-php, QR code libraries for 2FA).

2. **No Composer**
   - If dependencies are already bundled (e.g. `vendor/` committed), skip this step; only ensure the `vendor/` folder is present and readable by the web server.

### Step 6: Configure Web Server (if applicable)

1. **Apache**
   - Ensure `mod_rewrite` is enabled if the app uses clean URLs.  
   - Document root should point to the project folder (or the folder containing `index.php`).  
   - Restart Apache after changes.

2. **Nginx**
   - Point `root` to the project folder; set `index index.php`.  
   - Configure PHP-FPM and pass `.php` to the PHP handler.  
   - Restart Nginx and PHP-FPM.

3. **HTTPS (production)**
   - Use a certificate (e.g. Let’s Encrypt) and redirect HTTP to HTTPS in the server config.

### Step 7: First Run and Admin Account

1. **Open the application**
   - In a browser, go to `http://your-server/your-project-folder/` (or the configured URL).  
   - You should see the login page.

2. **Create first admin (if needed)**
   - If no admin account exists, use the provided setup/registration flow (if any) or insert an admin user directly in the database according to project documentation.  
   - Complete 2FA setup when prompted.  
   - Set security questions for password recovery if the system supports it.

3. **Test login**
   - Log in as admin; complete 2FA; confirm redirect to dashboard or admin area.

### Step 8: Optional Production Touches

- **Scheduled backups:** Configure Windows Task Scheduler or Linux cron to run `tools/scheduled_backup.php` (see DOCUMENTATION.md).  
- **Receipt printer:** Connect ESC/POS printer and configure as per project docs.  
- **Logging:** Ensure PHP error log and any application logs are written to a secure, non-public path.

---

## Post-Deployment Checklist

Use this checklist after deployment to ensure the system works correctly.

### Access and Authentication

- [ ] Login page loads at the correct URL.  
- [ ] Admin can log in with correct credentials.  
- [ ] Two-factor authentication (2FA) prompts and accepts valid code.  
- [ ] Cashier can log in and is restricted to cashier functions.  
- [ ] Logout works and redirects to login page.  
- [ ] Incorrect password shows appropriate error (and lockout if implemented).  
- [ ] “Forgot password” / recovery flow works if applicable.

### Admin Functions

- [ ] Dashboard loads and shows data (or “no data” as expected).  
- [ ] **User management:** Add, edit, and remove user (if allowed) work; new user can log in.  
- [ ] **Categories:** Add, edit, and remove category work.  
- [ ] **Products:** Add product (with image upload), edit product, remove product work.  
- [ ] **Account settings:** Update profile (name, username, email) and change password work.  
- [ ] **Backup:** “Backup database” downloads a valid `.sql` file.  
- [ ] **Restore:** Restore from a backup file completes without fatal errors (test on a copy if possible).  
- [ ] **Backup schedule:** Update schedule saves and (if applicable) scheduled task runs.  
- [ ] **Reports:** Generate report (e.g. sales report CSV) downloads and contains expected data.  
- [ ] **Transaction history:** List and filters work; receipt reprint logs or prints as designed.  
- [ ] **Log history:** Logs are visible; delete log works if implemented.

### Cashier / POS

- [ ] Take Out flow: add items, set quantity, checkout with Cash and/or GCash/PayMaya.  
- [ ] Dine In: add table, add items to table, checkout.  
- [ ] Discount (e.g. PWD/Senior) applies correctly.  
- [ ] Receipt prints (if printer is configured) or print preview/download works.  
- [ ] Transaction appears in Transaction History and in reports.

### Data and Security

- [ ] Database connection uses the intended credentials (no root in production).  
- [ ] Sensitive config files are not under the public document root or are protected.  
- [ ] HTTPS is used in production and HTTP redirects to HTTPS.  
- [ ] Session timeout and back-button prevention behave as documented.

### Performance and Stability

- [ ] No PHP errors or warnings on main pages (check error log).  
- [ ] Product images and uploads (e.g. backup file) work within size limits.  
- [ ] No mixed content (HTTP resources on HTTPS page) if using HTTPS.

### Backup and Recovery

- [ ] Manual backup runs and file is downloadable.  
- [ ] Restore procedure has been tested (e.g. on a copy of the database).  
- [ ] Scheduled backup (if configured) runs at the set time and saves to the expected location.

---

## Summary

| Phase                 | Action |
|-----------------------|--------|
| **Environment**       | Install/confirm web server, PHP 7.4+, MySQL/MariaDB, and required PHP extensions. |
| **Database**          | Create database, import SQL, create DB user, note credentials. |
| **Files**             | Upload project to document root; set permissions; install Composer dependencies. |
| **Configuration**     | Set `config/connection.php` and any backup/schedule paths. |
| **Verification**      | Use the post-deployment checklist to test login, admin, cashier, backup/restore, and security. |

After completing the deployment steps and the post-deployment checklist, the web-based POS system is ready for production use.
