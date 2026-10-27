# E-Atria HMS — Modern Hospital Management System

[![Framework](https://img.shields.io/badge/Framework-CodeIgniter%204.7-orange.svg)](https://codeigniter.com/)
[![PHP](https://img.shields.io/badge/PHP-8.3%20%7C%208.2-777bb4.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MySQL%208.0%20%7C%20MariaDB%2010.6+-00758f.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Open%20Source-green.svg)](LICENSE)
[![ABDM](https://img.shields.io/badge/ABDM-M1%20%7C%20M2%20%7C%20M3%20Compliant-blue.svg)](https://abdm.gov.in/)

**E-Atria HMS** is a 100% free and open-source Hospital Management System built on the modern, high-performance **CodeIgniter 4** PHP framework. Designed for multispeciality hospitals, nursing homes, polyclinics, and diagnostic centres, E-Atria HMS provides end-to-end clinical workflow automation — from OPD registration, doctor e-prescriptions, and IPD bed management to NABH discharge summaries, digital radiology templates, automated pathology analyzers, pharmacy billing, and complete **Ayushman Bharat Digital Mission (ABDM M1, M2, M3)** milestone compliance.

---

## 🌟 Key Features & Modules

- **OPD Management:** Token queue, biometric ABHA lookup, doctor clinical workspace, ICD-10 & SNOMED CT diagnoses, e-prescriptions, dynamic prescription print templates (A4/A5).
- **IPD Inpatient Care:** Bed management, nursing daily charts, vitals monitoring, OT scheduling, doctor rounds, bilingual legal consent forms (operative, high-risk, LAMA/DAMA).
- **NABH Discharge Summaries:** Speciality-wise clinical discharge templates (Medicine, Surgery, Cardiology, Ob/Gyn, Pediatrics, Orthopedics) with auto-calculated hospital stay and medication instructions.
- **Pathology Laboratory (LIS):** 37 clinical panels and 228 test parameters with LOINC codes, age/gender biological reference intervals, auto-differential calculation, and analyzer report printing.
- **Radiology & Imaging (RIS):** 59 master imaging templates across Ultrasound (USG: 27), 2D Echocardiography (ECHO: 9), Digital X-Ray (18), CT Scan (13), and MRI (14).
- **Pharmacy & Inventory:** Batch tracking, expiry alerts, GST invoices, vendor purchase orders, margin analysis, and Marg ERP item list compatibility.
- **Billing & Ayushman Bharat (PM-JAY):** 1,401 standardized Ayushman packages, corporate TPA claims, patient ledger, and detailed financial audits.
- **ABDM National Health Stack:** M1 (ABHA creation, Scan & Share), M2 (Care Context linking, OTP verification), and M3 (FHIR R4 clinical document bundle generation).

---

## 📋 Table of Contents

1. [Server Requirements](#1-server-requirements)
2. [Quick Installation: Method A (Automated 1-Command Script)](#2-quick-installation-method-a-automated-1-command-script)
3. [Manual Installation: Method B (Step-by-Step)](#3-manual-installation-method-b-step-by-step)
4. [Environment Configuration (.env)](#4-environment-configuration-env)
5. [Database Architecture & Starter Masters](#5-database-architecture--starter-masters)
6. [Comprehensive Medical Templates Library Seeding](#6-comprehensive-medical-templates-library-seeding)
7. [Web Server & SSL Configuration (Apache / Nginx)](#7-web-server--ssl-configuration-apache--nginx)
8. [File Permissions & Directory Hardening](#8-file-permissions--directory-hardening)
9. [Automated Background Cron Jobs](#9-automated-background-cron-jobs)
10. [First Login & Hospital Customization](#10-first-login--hospital-customization)
11. [Updating an Existing Installation](#11-updating-an-existing-installation)
12. [Useful Spark CLI Commands](#12-useful-spark-cli-commands)
13. [Official Documentation & Support](#13-official-documentation--support)

---

## 1. Server Requirements

| Component | Minimum | Recommended Production Spec |
| :--- | :--- | :--- |
| **Operating System** | Ubuntu 20.04 LTS / Debian 11 | **Ubuntu 24.04 LTS** or **Ubuntu 22.04 LTS** |
| **Hardware Resources** | 2 vCPU, 4 GB RAM, 25 GB SSD | **4 vCPU, 8 GB RAM, 80 GB NVMe SSD** (50+ clinical users) |
| **PHP Version** | PHP 8.2 | **PHP 8.3** (FPM or Apache mod-php) |
| **Database** | MySQL 5.7 / MariaDB 10.4 | **MySQL 8.0+** (UTF8MB4 charset, InnoDB) |
| **Web Server** | Apache 2.4 / Nginx 1.18+ | **Apache 2.4** with `mod_rewrite`, `mod_ssl`, `mod_headers`, `mod_deflate` |
| **Package Managers** | Composer 2.2+, Git 2.30+ | **Composer 2.7+**, **Git 2.34+** |

**Required PHP Extensions:**
```bash
php8.3-cli php8.3-fpm php8.3-mysql php8.3-curl php8.3-gd php8.3-mbstring php8.3-xml php8.3-intl php8.3-zip php8.3-bcmath
```

**Recommended `php.ini` Settings:**
```ini
memory_limit = 512M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
max_input_vars = 5000
date.timezone = Asia/Kolkata
display_errors = Off
expose_php = Off
```

---

## 2. Quick Installation: Method A (Automated 1-Command Script)

For fresh Ubuntu servers (20.04, 22.04, or 24.04 LTS), the repository includes an automated installation script located at `scripts/install_ubuntu.sh`:

```bash
# 1. Clone repository to server
sudo mkdir -p /var/www/html/eatria_client
cd /var/www/html/eatria_client
sudo git clone https://git.e-atria.net/d_s_bisht/devsofttech_hms_ci4.git hospital

# 2. Run automated installation script
cd hospital
sudo bash scripts/install_ubuntu.sh <domain_name> <database_name> "<hospital_name>"
```

**Real-World Example:**
```bash
sudo bash scripts/install_ubuntu.sh hospital.yourdomain.com hms_live_db "City Multispeciality Hospital"
```

*Interactive Prompt Mode:* Running `sudo bash scripts/install_ubuntu.sh` without arguments will prompt for domain, database, hospital name, and MySQL credentials.

---

## 3. Manual Installation: Method B (Step-by-Step)

### Step 1: Install System Packages
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 mysql-server git unzip curl ca-certificates certbot python3-certbot-apache

# Install PHP 8.3 & extensions
sudo apt install -y \
  php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-curl \
  php8.3-gd php8.3-mbstring php8.3-xml php8.3-intl php8.3-zip php8.3-bcmath libapache2-mod-php8.3

# Enable Apache modules
sudo a2enmod rewrite ssl headers deflate
sudo systemctl restart apache2
```

### Step 2: Clone Repository & Install Dependencies
```bash
sudo mkdir -p /var/www/html/eatria_client/hospital
sudo git clone https://git.e-atria.net/d_s_bisht/devsofttech_hms_ci4.git /var/www/html/eatria_client/hospital

cd /var/www/html/eatria_client/hospital
composer install --no-dev --optimize-autoloader
```

### Step 3: Create MySQL Database
```sql
CREATE DATABASE IF NOT EXISTS `hms_hospital_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'hms_user'@'localhost' IDENTIFIED BY 'YourStrongPassword123!';
GRANT ALL PRIVILEGES ON `hms_hospital_db`.* TO 'hms_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 4: Configure Environment File (.env)
```bash
cp env .env
nano .env
# Edit database and app.baseURL (see Section 4)
```

### Step 5: Initialize Schema & Superadmin User
Execute the built-in Spark CLI installer:
```bash
php spark hms:install \
  --hospital-name="City Multispeciality Hospital" \
  --admin-user="admin" \
  --admin-email="admin@hospital.com" \
  --admin-password="YourStrongPassword"
```

*Or import clean starter schema directly:*
```bash
mysql -u root -p hms_hospital_db < database/hms_starter_schema_and_masters.sql
```

---

## 4. Environment Configuration (.env)

Edit `.env` inside the project root:

```dotenv
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------
CI_ENVIRONMENT = production

#--------------------------------------------------------------------
# APPLICATION
#--------------------------------------------------------------------
app.baseURL = 'https://hospital.yourdomain.com/'
app.appTimezone = 'Asia/Kolkata'

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------
database.default.hostname = 127.0.0.1
database.default.database = hms_hospital_db
database.default.username = hms_user
database.default.password = YourStrongPassword123!
database.default.DBDriver = MySQLi
database.default.port     = 3306
database.default.DBDebug  = false

# Database Maintenance Key
setup.dbToolsKey = yourSecureKey2026

#--------------------------------------------------------------------
# SNOMED CT CSNOTK TERMINOLOGY SERVICE
#--------------------------------------------------------------------
snomed.csnotk.baseUrl = https://csnotk.e-atria.in/csnoserv
snomed.csnotk.enabled = true
snomed.csnotk.timeoutSec = 10

#--------------------------------------------------------------------
# ABDM BRIDGE GATEWAY CONNECTOR
#--------------------------------------------------------------------
ABDM_BRIDGE_PUBLIC_URL = https://abdm-bridge.e-atria.in
abdm.connector = dreamsoft
abdm.directBaseUrl = https://dev.abdm.gov.in/api/v3
abdm.directTimeoutSec = 30
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge
ABDM_BRIDGE_URL = https://abdm-bridge.e-atria.in/api/v1/bridge
BRIDGE_SYNC_TIMEOUT = 20
BRIDGE_SYNC_PROVIDER = bridge
```

Generate application encryption key:
```bash
php spark key:generate
```

---

## 5. Database Architecture & Starter Masters

The pre-built database (`database/hms_starter_schema_and_masters.sql`):
- **399 Total Tables:** Complete enterprise schema with relational constraints and composite indexes.
- **Zero-Patient Clean State:** All clinical and transactional tables start empty (`patient_master` = 0 rows, `opd_master` = 0 rows, `ipd_master` = 0 rows, `invoice_master` = 0 rows).
- **83,000+ Preloaded Master Records:**
  - **Ayushman Packages:** 1,401 standardized PM-JAY packages (`ayushman_package_master`).
  - **Drug Timings & Frequencies:** `opd_dose_frequency`, `opd_dose_when`, `opd_dose_where`, `opd_dose_duration`.
  - **Charge Items & Departments:** `hc_department`, `hc_items`, `ipd_item_type`.
  - **Reference Catalogues:** `blood_group`, `color`, `india_state`, `med_company`, `med_formulation`.
  - **Security & RBAC:** CodeIgniter Shield auth with Superadmin account pre-configured.

---

## 6. Comprehensive Medical Templates Library Seeding

E-Atria HMS includes a hospital-wide Medical Templates Library that populates out-of-the-box clinical templates:

```bash
php spark db:seed MedicalTemplatesLibrarySeeder
```

> **🛡️ Insert-If-Not-Exists Safety Guarantee:**  
> The seeder runs in **Safe Mode**. It checks whether each template or test parameter already exists in your database. **If a record exists, it is NEVER overwritten or updated**, preserving all custom test rates, personalized normal reference intervals, and doctor notes. Only missing or newly added templates are inserted.

### What Gets Seeded Out-of-the-Box:
- 🩺 **37 Pathology Panels & 228 Test Parameters:** Complete Blood Count (CBC with ESR), LFT, KFT/RFT, Lipid Profile, Thyroid Profile (Total & Free), Urine Routine & Microscopic, HbA1c, Coagulation Profile (PT/INR/APTT), Semen Analysis, CSF Analysis, Dengue Serology, Sputum AFB, Widal, and Stool Routine. Includes standardized LOINC codes, adult male/female reference intervals, units, and HTML table formats.
- 🩻 **59 Radiology Master Templates across 5 Modalities:**
  - **Ultrasound (USG - 27 templates):** Whole Abdomen & Pelvis, KUB, TVS, Obstetric (Early dating, Growth, Anomaly Level-II / TIFFA, Color Doppler), Scrotum, Thyroid, Breast, Musculoskeletal.
  - **Echocardiography (ECHO - 9 templates):** 2D Echocardiography with Color Doppler, Stress Echo, Pediatric Congenital Echo.
  - **Digital X-Ray (18 templates):** Chest PA/AP, KUB, Cervical Spine, Lumbar Spine, Skull, Pelvis with Hip, Extremities.
  - **CT Scan (13 templates):** NCCT / CECT Brain, HRCT Chest, CT Abdomen & Pelvis, CT KUB, CT Angiography.
  - **MRI (14 templates):** MRI Brain with DWI, MRI Cervical/Lumbar Spine, MRI Knee Joint, MRI Shoulder Joint, MRCP.
- 📄 **7 NABH/ABDM IPD Discharge Summaries:** General Medicine, General Surgery, Cardiology, Obstetrics & Gynaecology, Pediatrics & Neonatology, Orthopedics, and Day Care Surgeries.
- ✍️ **36 Master Clinical Narratives:** Pre-written standardized medical narratives for Diagnosis Remarks, Hospital Course, In-Hospital Treatment, and Post-Discharge Instructions.
- 📋 **13 Bilingual IPD Document Forms:** High-Risk Operative Consent, General Inpatient Admission Consent, LAMA/DAMA Refusal, Blood Transfusion Consent, Patient Property Handover, and Financial Clearance.
- 🩺 **29 OPD Doctor Clinical Templates:** Speciality-wise examination findings, provisional diagnoses, final diagnoses, prescriber remarks, and follow-up advice.
- 🖨️ **3 OPD Prescription Print Layouts:** Standard Header/Footer, Compact A5, and Pediatric specialized formats.

---

## 7. Web Server & SSL Configuration (Apache / Nginx)

Point your virtual host `DocumentRoot` strictly to the **`public/`** folder — **not** the project root.

### Apache Configuration (`/etc/apache2/sites-available/hospital.conf`):
```apache
<VirtualHost *:80>
    ServerName hospital.yourdomain.com
    ServerAdmin webmaster@yourdomain.com

    DocumentRoot /var/www/html/eatria_client/hospital/public

    <Directory /var/www/html/eatria_client/hospital/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html

        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php/$1 [L]
        </IfModule>
    </Directory>

    <IfModule mod_headers.c>
        Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-Content-Type-Options "nosniff"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
        Header always set X-XSS-Protection "1; mode=block"
    </IfModule>

    ErrorLog ${APACHE_LOG_DIR}/hospital_error.log
    CustomLog ${APACHE_LOG_DIR}/hospital_access.log combined
</VirtualHost>
```

Enable site and install free SSL certificate:
```bash
sudo a2ensite hospital.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
sudo certbot --apache -d hospital.yourdomain.com
```

---

## 8. File Permissions & Directory Hardening

The web server user (`www-data`) must have write access only to `writable/` and `public/assets/images`:

```bash
cd /var/www/html/eatria_client/hospital

# Ensure subdirectories exist
sudo mkdir -p \
  writable/cache \
  writable/logs \
  writable/session \
  writable/uploads \
  writable/debugbar \
  public/assets/images

# Assign ownership
sudo chown -R www-data:www-data /var/www/html/eatria_client/hospital

# Secure permissions
sudo chmod -R 755 /var/www/html/eatria_client/hospital
sudo chmod -R 775 writable public/assets/images

# Lock down .env file
sudo chmod 640 .env
sudo chown www-data:www-data .env
```

---

## 9. Automated Background Cron Jobs

Add the following background tasks using `sudo crontab -e`:

```cron
# Process ABDM Work Task Board Care Context Linking every 2 minutes
*/2 * * * * cd /var/www/html/eatria_client/hospital && php spark abdm:taskboard-sync > /dev/null 2>&1

# Push stored health records to ABDM Bridge every 5 minutes
*/5 * * * * cd /var/www/html/eatria_client/hospital && php spark abdm:push-sync > /dev/null 2>&1

# Prune old ABDM logs daily at 02:00 AM
0 2 * * * cd /var/www/html/eatria_client/hospital && php spark abdm:prune-logs > /dev/null 2>&1
```

---

## 10. First Login & Hospital Customization

1. Open your browser: `https://hospital.yourdomain.com/login`
2. Sign in with your administrator credentials:
   - **Username / Email:** `admin` (or configured email)
   - **Password:** `Admin@12345` (or configured password)
   - **CAPTCHA:** Enter the 4-digit code displayed on screen.
3. Once logged in:
   - Go to **Setting → Hospital Profile** (`/setting/admin/hospital-profile`) to customize your Hospital Name, Address, Contact details, GSTIN, and upload your high-resolution Hospital Logo and branded Login Wallpaper.
   - Go to **Setting → Medical Templates** (`/setting/template`) to review or customize your pre-loaded Pathology, Radiology, and Discharge layouts.

---

## 11. Updating an Existing Installation

```bash
# 1. Navigate to project root
cd /var/www/html/eatria_client/hospital

# 2. Pull latest code from official repository
git pull origin main

# 3. Update Composer packages
composer install --no-dev --optimize-autoloader

# 4. Run database migrations (safe - skips already applied migrations)
php spark migrate

# 5. Seed newly added clinical templates (preserves existing hospital data)
php spark db:seed MedicalTemplatesLibrarySeeder
```

---

## 12. Useful Spark CLI Commands

```bash
# Show all CLI commands
php spark list

# Run interactive automated installer
php spark hms:install

# Seed Medical Templates Library (Safe Mode)
php spark db:seed MedicalTemplatesLibrarySeeder

# Run pending database migrations
php spark migrate

# Rollback last migration batch
php spark migrate:rollback

# Synchronize ABDM Care Contexts & Health Records
php spark abdm:taskboard-sync
php spark abdm:push-sync

# Check database tables
php spark db:table hospital_setting

# Built-in development server (local only)
php spark serve --host 0.0.0.0 --port 8080
```

---

## 13. Official Documentation & Support

- **Ubuntu Installation Guide:** [docs/UBUNTU_INSTALLATION_GUIDE.md](docs/UBUNTU_INSTALLATION_GUIDE.md)
- **ABDM Care Context & FHIR Standards:** [docs/abdm/CARE_CONTEXT_AND_FHIR_STANDARDS.md](docs/abdm/CARE_CONTEXT_AND_FHIR_STANDARDS.md)
- **Online Documentation Portal:** [https://www.e-atria.net/docs](https://www.e-atria.net/docs)
- **Website & Cloud Services:** [https://www.e-atria.net](https://www.e-atria.net)
- **Software License:** [SOFTWARE_USE_LICENSE.md](SOFTWARE_USE_LICENSE.md)
- **Technical Support & Turnkey Deployments:** [info@e-atria.net](mailto:info@e-atria.net)
