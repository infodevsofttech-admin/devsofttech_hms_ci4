# HMS — Hospital Management System (CI4)

**Framework:** CodeIgniter 4.7 · **PHP:** 8.2+ · **DB:** MySQL 5.7+ / MariaDB 10.4+

A full-featured hospital management system covering OPD, IPD, Billing, Pathology Lab,
Radiology, Finance, Pharmacy Stock, and more.

---

## Table of Contents

1. [Server Requirements](#1-server-requirements)
2. [Fresh Installation](#2-fresh-installation)
3. [Web Server Configuration](#3-web-server-configuration)
4. [Environment Configuration](#4-environment-configuration)
5. [Database Setup](#5-database-setup)
6. [Seed Master Data](#6-seed-master-data)
7. [File Permissions](#7-file-permissions)
8. [First Login](#8-first-login)
9. [Updating an Existing Installation](#9-updating-an-existing-installation)
10. [OEM / Multi-Site Deployment](#10-oem--multi-site-deployment)
11. [Lab Template Workflow](#11-lab-template-workflow)
12. [Useful Spark Commands](#12-useful-spark-commands)
13. [Project Documents](#13-project-documents)

---

## 1. Server Requirements

| Requirement | Minimum | Recommended |
|---|---|---|
| PHP | 8.2 | 8.3 |
| MySQL | 5.7 | 8.0+ |
| MariaDB (alternative) | 10.4 | 10.11+ |
| Web server | Apache 2.4 / Nginx | Apache with `mod_rewrite` |
| Composer | 2.x | latest |

**Required PHP extensions:**

```
intl  mbstring  mysqlnd  json  curl  gd  zip  fileinfo  xml
```

**Recommended `php.ini` settings:**

```ini
memory_limit       = 256M
upload_max_filesize = 20M
post_max_size       = 25M
max_execution_time  = 120
```

---

## 2. Fresh Installation

```bash
# 1. Clone the repository
git clone https://github.com/infodevsofttech-admin/devsofttech_hms_ci4.git hms
cd hms

# 2. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 3. Copy environment file
cp env .env

# 4. Edit .env  (see Section 4)
nano .env

# 5. Run database migrations
php spark migrate

# 6. Seed all master data  (see Section 6)
php spark db:seed LabTemplateSeeder
php spark db:seed InvestigationMasterSeeder
php spark db:seed ClinicalTemplateWorkspaceSeeder
php spark db:seed ComplaintsMasterSeeder
```

> **Windows (WAMP/Laragon):** use `php spark` from the project root in Command Prompt or PowerShell.

---

## 3. Web Server Configuration

### Apache

Point your virtual host `DocumentRoot` to the **`public/`** folder — **not** the project root.

```apache
<VirtualHost *:80>
    ServerName hms.yourhospital.in
    DocumentRoot /var/www/hms/public

    <Directory /var/www/hms/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

`mod_rewrite` must be enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Nginx

```nginx
server {
    listen 80;
    server_name hms.yourhospital.in;
    root /var/www/hms/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

---

## 4. Environment Configuration

Edit `.env` (copied from `env` in step 3):

```dotenv
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------
CI_ENVIRONMENT = production          # use "development" while installing

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------
app.baseURL = 'https://hms.yourhospital.in/'
app.appTimezone = 'Asia/Kolkata'     # adjust to your timezone

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------
database.default.hostname = localhost
database.default.database = hms_ci4
database.default.username = hms_user
database.default.password = StrongPassword123
database.default.DBDriver = MySQLi
database.default.port     = 3306

#--------------------------------------------------------------------
# ENCRYPTION KEY (generate once: php spark key:generate)
#--------------------------------------------------------------------
# encryption.key = hex2bin:...
```

Generate encryption key:

```bash
php spark key:generate
```

### ABDM via Eka.care (existing routes, provider switch)

This project keeps the same ABDM controller routes and queue events, and can switch
the queue dispatcher to Eka for ABDM/NHCX integrations.

Add these keys in `.env`:

```dotenv
ABDM_SYNC_PROVIDER = eka
EKA_BASE_URL = https://api.eka.care
EKA_BEARER_TOKEN = your-token
# Optional alternatives
# EKA_API_KEY = your-api-key
# EKA_CLIENT_ID = your-client-id
# EKA_EVENT_ENDPOINTS_JSON = {"abdm.abha.validate":"/your/path"}
# EKA_WEBHOOK_SECRET = shared-secret-for-callback-signature
```

Then process queue normally:

```bash
php spark bridge:sync
```

If `EKA_WEBHOOK_SECRET` is set, callback endpoints require a valid HMAC-SHA256 signature
in `X-Eka-Signature`, `X-Signature`, or `X-Hub-Signature-256`.

---

## 5. Database Setup

### Create database and user (MySQL)

```sql
CREATE DATABASE hms_ci4 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'hms_user'@'localhost' IDENTIFIED BY 'StrongPassword123';
GRANT ALL PRIVILEGES ON hms_ci4.* TO 'hms_user'@'localhost';
FLUSH PRIVILEGES;
```

### Run migrations

Migrations create all application tables (billing, IPD, finance, stock, etc.):

```bash
php spark migrate
```

> The legacy lab/radiology tables (`lab_repo`, `lab_rgroups`, `lab_tests`, etc.) are
> **not** managed by migrations — they are created automatically by the seeders in Step 6.

---

## 6. Seed Master Data

Run in this order:

```bash
# Lab & radiology report templates (creates tables too)
php spark db:seed LabTemplateSeeder

# Investigation/test master list for OPD order panel
php spark db:seed InvestigationMasterSeeder

# OPD clinical workspace templates (complaint, examination, advice templates)
php spark db:seed ClinicalTemplateWorkspaceSeeder

# Complaint master list
php spark db:seed ComplaintsMasterSeeder
```

All seeders are **idempotent** — safe to re-run; they use `INSERT IGNORE`.

### Optional: OPD demo data

```bash
# Loads all OPD master data at once (calls Clinical + Investigation seeders)
php spark db:seed OpdDemoMasterSeeder
```

---

## 7. File Permissions

The web server user (`www-data` on Ubuntu, `apache` on CentOS) must have write access
to these directories:

```bash
# Linux / CentOS / Ubuntu
chmod -R 775 writable/
chmod -R 775 public/assets/uploads/
chmod -R 775 public/uploads/

chown -R www-data:www-data writable/ public/assets/uploads/ public/uploads/
```

> On Windows (WAMP/Laragon) no permission changes are needed.

---

## 8. First Login

1. Open `https://hms.yourhospital.in/` in your browser.
2. Register the **first user** — this account automatically becomes `superadmin`.
3. Go to **Setup → Hospital Info** and enter your hospital name, address, and logo.
4. Go to **Setup → Users** to create additional staff accounts and assign roles.

**Available roles:**

| Role | Access |
|---|---|
| `superadmin` | Full system access |
| `admin` | Day-to-day administration |
| `stock_manager` | Stock masters, approvals, procurement |
| `storekeeper` | Item issuance and stock receiving |
| `department_head` | Department stock indents |
| `user` | General / reception access |

---

## 9. Updating an Existing Installation

```bash
# Pull latest code
git pull origin main

# Install any new Composer packages
composer install --no-dev --optimize-autoloader

# Run any new migrations
php spark migrate

# If lab templates were updated by the developer, re-seed them
php spark db:seed LabTemplateSeeder
```

> Always run `php spark migrate` after every `git pull` — it is safe to run on an
> already-migrated database (it skips already-applied migrations).

---

## 10. OEM / Multi-Site Deployment

For deploying to multiple hospital sites from one codebase:

1. Follow Steps 2–8 on each server.
2. Each site gets its own `.env` with its own database credentials and `baseURL`.
3. When the main developer adds new features/templates, sites update with:

```bash
git pull
php spark migrate
php spark db:seed LabTemplateSeeder    # if lab templates were exported
```

---

## 11. Lab Template Workflow

Lab and radiology report templates are stored in the database. When templates are
added or edited via the admin UI, the developer exports them so all OEM sites can
receive them via git:

**On the main/development server (after editing templates in UI):**

```bash
php spark oem:export-lab-templates
git add app/Database/Seeds/LabOemData/
git commit -m "OEM: update lab template seed data"
git push
```

**On each remote/OEM server:**

```bash
git pull
php spark db:seed LabTemplateSeeder
```

Tables covered by this workflow:

| Table | Description |
|---|---|
| `lab_rgroups` | Report group names (Haematology, BioChemistry …) |
| `hc_items` (itype 5,6) | Billing charge items linked to lab reports |
| `lab_repo` | Pathology report templates (with HTML layout) |
| `lab_tests` | Individual test / parameter definitions |
| `lab_tests_option` | Dropdown options for test parameters |
| `lab_repotests` | Report ↔ test assignments and sort order |
| `radiology_ultrasound_template` | US / MRI / CT / Xray / Echo templates |

---

## 12. Useful Spark Commands

```bash
# Show all available commands
php spark list

# Run pending migrations
php spark migrate

# Rollback last migration batch
php spark migrate:rollback

# Seed a specific seeder
php spark db:seed LabTemplateSeeder

# Export current lab templates to git-trackable SQL files
php spark oem:export-lab-templates

# Import Ayushman package master from Excel/CSV
php spark ayushman:import

# Generate a new encryption key (first install only)
php spark key:generate

# Built-in development server (local only)
php spark serve
```

---

## 13. Project Documents

- Full software help guide: [docs/HOSPITAL_STOCK_HELP.md](docs/HOSPITAL_STOCK_HELP.md)
- Software use license: [SOFTWARE_USE_LICENSE.md](SOFTWARE_USE_LICENSE.md)
- Open-source notice: [OPEN_SOURCE_NOTICE.md](OPEN_SOURCE_NOTICE.md)
