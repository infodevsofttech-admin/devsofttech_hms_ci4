# E-Atria HMS (Hospital Management System) — Ubuntu Installation Guide

This guide provides instructions for deploying and installing **E-Atria HMS (CodeIgniter 4)** on an **Ubuntu Linux server** (Ubuntu 20.04, 22.04, or 24.04 LTS).

---

## Table of Contents
1. [System Prerequisites](#1-system-prerequisites)
2. [Method A: Automated 1-Command Installation (Recommended)](#2-method-a-automated-1-command-installation-recommended)
3. [Method B: Manual Step-by-Step Installation](#3-method-b-manual-step-by-step-installation)
4. [Database Architecture & Starter Data](#4-database-architecture--starter-data)
5. [Configuring Apache & SSL](#5-configuring-apache--ssl)
6. [Post-Installation Verification & First Login](#6-post-installation-verification--first-login)
7. [Recommended PHP & Server Settings](#7-recommended-php--server-settings)
8. [Automated Background Cron Jobs](#8-automated-background-cron-jobs)
9. [Troubleshooting Common Issues](#9-troubleshooting-common-issues)

---

## 1. System Prerequisites

| Component | Recommended Version | Details |
| :--- | :--- | :--- |
| **Operating System** | Ubuntu 22.04 / 24.04 LTS | Standard server image |
| **Web Server** | Apache 2.4+ | Modules: `rewrite`, `ssl`, `headers` |
| **PHP** | PHP 8.2 or 8.3 | Required extensions listed below |
| **Database** | MySQL 8.0+ or MariaDB 10.5+ | UTF8MB4 charset support |
| **Package Managers** | Git & Composer 2+ | For code updates and dependencies |

### Required PHP Extensions:
- `php8.3-cli`, `php8.3-fpm` (or `libapache2-mod-php8.3`)
- `php8.3-mysql`, `php8.3-curl`, `php8.3-gd`, `php8.3-mbstring`, `php8.3-xml`, `php8.3-intl`, `php8.3-zip`, `php8.3-bcmath`

---

## 2. Method A: Automated 1-Command Installation (Recommended)

The repository provides an automated installation script in [`scripts/install_ubuntu.sh`](file:///d:/Workplace/HMS_CI4_OLD/scripts/install_ubuntu.sh).

### Syntax:
```bash
sudo bash scripts/install_ubuntu.sh <domain_name> <database_name> "<hospital_name>"
```

### Real-World Example:
```bash
sudo bash scripts/install_ubuntu.sh johrihospital.e-atria.net hms_johri_ss_hospital "Johri Hospital"
```

### Interactive Mode:
If you run without arguments, the script will interactively prompt for:
- Domain Name (e.g. `johrihospital.e-atria.net`)
- Database Name (e.g. `hms_johri_ss_hospital`)
- Hospital Name (e.g. `Johri Hospital`)
- MySQL Root Password

The script will automatically:
1. Update system packages and install Apache, PHP 8.3, MySQL client, Composer, and Git.
2. Enable required Apache modules (`rewrite`, `ssl`, `headers`).
3. Clone or pull the repository to `/var/www/html/eatria_client/<slug>` or `/var/www/html/<slug>`.
4. Create the MySQL database if it does not already exist.
5. Create `.env` configured with your database and domain.
6. Populate all 399 tables with the clean master schema, templates, and clinical datasets.
7. Setup the Superadmin user account (`admin` / `Admin@12345`).
8. Generate and activate the Apache VirtualHost with SSL.
9. Test and verify HTTP 200 response on `/login`.

---

## 3. Method B: Manual Step-by-Step Installation

If you prefer full manual control, follow these steps:

### Step 1: Install System Packages
```bash
sudo apt-get update
sudo apt-get install -y apache2 mysql-server git unzip curl ca-certificates

# Install PHP 8.3 and extensions
sudo apt-get install -y \
  php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-curl \
  php8.3-gd php8.3-mbstring php8.3-xml php8.3-intl php8.3-zip php8.3-bcmath

# Enable Apache modules
sudo a2enmod rewrite ssl headers proxy proxy_fcgi
sudo systemctl restart apache2
```

### Step 2: Clone the Application
```bash
sudo mkdir -p /var/www/html/eatria_client/johrihospital
sudo git clone https://git.e-atria.net/d_s_bisht/devsofttech_hms_ci4.git /var/www/html/eatria_client/johrihospital

cd /var/www/html/eatria_client/johrihospital

# Install Composer dependencies
composer install --no-dev --optimize-autoloader
```

### Step 3: Configure Writable Directory Permissions
CodeIgniter 4 requires write permissions on several cache and storage directories:
```bash
cd /var/www/html/eatria_client/johrihospital

# Ensure all subdirectories exist
sudo mkdir -p \
  writable/cache \
  writable/logs \
  writable/session \
  writable/uploads \
  writable/debugbar \
  public/assets/images

# Assign ownership to web server
sudo chown -R www-data:www-data /var/www/html/eatria_client/johrihospital
sudo chmod -R 775 writable public/assets/images
```

### Step 4: Configure Environment (`.env`)
Create `.env` in the application root directory:
```bash
cat << 'EOF' > .env
CI_ENVIRONMENT = production

app.baseURL = 'https://johrihospital.e-atria.net/'

database.default.hostname = localhost
database.default.username = root
database.default.password = YourStrongPassword
database.default.database = hms_johri_ss_hospital
database.default.DBDriver = MySQLi
database.default.port = 3306
database.default.DBDebug = false

setup.dbToolsKey = yourStrongSetupKey123

snomed.csnotk.baseUrl = https://csnotk.e-atria.in/csnoserv
snomed.csnotk.enabled = true
snomed.csnotk.timeoutSec = 10

ABDM_BRIDGE_PUBLIC_URL=https://abdm-bridge.e-atria.in
abdm.connector = dreamsoft
abdm.directBaseUrl = https://dev.abdm.gov.in/api/v3
abdm.directTimeoutSec = 30
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge
ABDM_BRIDGE_URL = https://abdm-bridge.e-atria.in/api/v1/bridge
BRIDGE_SYNC_TIMEOUT = 20
BRIDGE_SYNC_PROVIDER = bridge
EOF

sudo chmod 640 .env
sudo chown www-data:www-data .env
```

### Step 5: Initialize Database & Run Installer
Create the database in MySQL:
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS \`hms_johri_ss_hospital\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

Run the built-in Spark installation command (automatically seeds schema and templates):
```bash
php spark hms:install \
  --hospital-name="Johri Hospital" \
  --admin-user="admin" \
  --admin-email="admin@example.com" \
  --admin-password="Admin@12345"
```

### Seeding the Comprehensive Medical Templates Library:
To populate or update all out-of-the-box Pathology panels (37 panels, 228 tests with LOINC), Radiology templates (59 templates across USG, ECHO, X-Ray, CT, MRI), 7 NABH IPD discharge summaries, 36 clinical narratives, 13 IPD consent forms, 29 OPD doctor templates, and 3 prescription print formats:
```bash
php spark db:seed MedicalTemplatesLibrarySeeder
```
> **Safe Mode Guarantee (Insert-If-Not-Exists):** The seeder strictly checks if records already exist. It **never overwrites or updates** existing templates or tests, preserving all custom test rates, personalized normal reference intervals, and doctor notes. Only missing or newly added templates are inserted.

*Alternatively*, import the starter SQL file directly via MySQL CLI:
```bash
# Uncompressed
mysql -u root -p hms_johri_ss_hospital < database/hms_starter_schema_and_masters.sql

# Gzipped
zcat database/hms_starter_schema_and_masters.sql.gz | mysql -u root -p hms_johri_ss_hospital
```

---

## 4. Database Architecture & Starter Data

The starter database ([`database/hms_starter_schema_and_masters.sql`](file:///d:/Workplace/HMS_CI4_OLD/database/hms_starter_schema_and_masters.sql)):
- **399 Total Tables**: Complete HMS schema with all relations, primary keys, and indexes.
- **Clean State**: Transactional tables start with **0 rows**:
  - `patient_master`, `opd_master`, `opd_prescription` = 0 rows
  - `ipd_master`, `ipd_discharge`, `invoice_master`, `payment_history` = 0 rows
  - `sales_master`, `purchase_invoice`, `lab_request`, `abdm_api_logs` = 0 rows
- **Pre-populated Master Tables & Templates** (186 master tables with 83,000+ records):
  - **Clinical Templates**: `opd_clinical_templates`, `ipd_discharge_templates`, `doc_format_master`, `doc_print_templates`, `diagnosis_print_templates`
  - **Drug Timings & Frequencies**: `opd_dose_frequency`, `opd_dose_when`, `opd_dose_where`, `opd_dose_duration`
  - **Investigation & Lab Masters**: `investigation`, `invprofiles`, `lab_tests`, `lab_rgroups`
  - **Ayushman Packages**: `ayushman_package_master` (1,401 standardized PM-JAY packages)
  - **Departments & Charge Items**: `hc_department`, `hc_items`, `ipd_item_type`
  - **Reference Lists**: `blood_group`, `color`, `india_state`, `name_list`, `med_company`, `med_formulation`
  - **Shield Auth & Superadmin**: `users`, `auth_identities`, `auth_groups_users`
  - **Database Migrations Record**: `migrations` (all 132 migrations recorded as up-to-date)

---

## 5. Configuring Apache & SSL

Create `/etc/apache2/sites-available/johrihospital.conf`:

```apache
<VirtualHost *:80>
    ServerName johrihospital.e-atria.net
    Redirect permanent / https://johrihospital.e-atria.net/
</VirtualHost>

<VirtualHost *:443>
    ServerName johrihospital.e-atria.net

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/e-atria.net/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/e-atria.net/privkey.pem

    DocumentRoot /var/www/html/eatria_client/johrihospital/public

    <Directory /var/www/html/eatria_client/johrihospital/public>
        Options +FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html index.htm

        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php/$1 [L]
        </IfModule>
    </Directory>

    ErrorLog /var/log/apache2/johrihospital_error.log
    CustomLog /var/log/apache2/johrihospital_access.log combined
</VirtualHost>
```

### Enable Site and Reload Apache:
```bash
sudo a2ensite johrihospital.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

---

## 6. Post-Installation Verification & First Login

1. Open your browser and navigate to:
   ```
   https://johrihospital.e-atria.net/login
   ```
2. Log in using default superadmin credentials:
   - **Username / Email:** `admin` (or `devender.singh.bisht@gmail.com`)
   - **Password:** `Admin@12345`
3. Enter the 4-digit Security CAPTCHA code shown on the screen and click **Login**.
4. Once logged in:
   - Navigate to **Setting -> Hospital Profile** (`/setting/admin/hospital-profile`) to customize Hospital Name, Address, Contact details, Hospital Logo, and Login Wallpaper.

---

## 7. Recommended PHP & Server Settings

In `/etc/php/8.3/apache2/php.ini` (or `/etc/php/8.3/fpm/php.ini`):

```ini
memory_limit = 512M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
max_input_vars = 5000
date.timezone = Asia/Kolkata
```

Reload web server after changes:
```bash
sudo systemctl restart apache2
# or sudo systemctl restart php8.3-fpm
```

---

## 8. Automated Background Cron Jobs

To ensure seamless ABDM synchronization, SMS notifications, and background log pruning, add the following to `crontab -e`:

```cron
# Process ABDM Work Task Board Care Context Linking every 2 minutes
*/2 * * * * cd /var/www/html/eatria_client/johrihospital && php spark abdm:taskboard-sync > /dev/null 2>&1

# Push stored health records to ABDM Bridge every 5 minutes
*/5 * * * * cd /var/www/html/eatria_client/johrihospital && php spark abdm:push-sync > /dev/null 2>&1

# Prune old ABDM logs daily at 02:00 AM
0 2 * * * cd /var/www/html/eatria_client/johrihospital && php spark abdm:prune-logs > /dev/null 2>&1
```

---

## 9. Troubleshooting Common Issues

### Issue 1: HTTP 500 - `CacheException::forUnableToWrite()`
- **Cause:** Missing `writable/cache` directory.
- **Fix:**
  ```bash
  sudo mkdir -p /var/www/html/eatria_client/johrihospital/writable/cache
  sudo chown -R www-data:www-data /var/www/html/eatria_client/johrihospital/writable
  sudo chmod -R 775 /var/www/html/eatria_client/johrihospital/writable
  ```

### Issue 2: 404 Not Found on Inner Routes (e.g. `/login`, `/dashboard`)
- **Cause:** Apache `mod_rewrite` is disabled, or `.htaccess` is ignored because `AllowOverride None` is set.
- **Fix:**
  ```bash
  sudo a2enmod rewrite
  # Ensure "AllowOverride All" is set in <Directory .../public> in the virtual host config
  sudo systemctl restart apache2
  ```

### Issue 3: MySQL "Access Denied" or Strict Mode Error
- **Cause:** Incorrect credentials in `.env` or MySQL strict group-by mode.
- **Fix:** Verify database settings in `.env` and test connection:
  ```bash
  cd /var/www/html/eatria_client/johrihospital
  php spark db:table hospital_setting
  ```
