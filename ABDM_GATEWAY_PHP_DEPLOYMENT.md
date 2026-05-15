# PHP ABDM Gateway Deployment Guide

## Architecture Overview

```
HMS CI4 (main app)
    ↓ (queue event: event_type + payload)
    BridgeSyncService.processPending()
    ↓ POST with Bearer token
https://abdm-bridge.e-atria.in/api/v1/bridge
    ↓ (PHP gateway routes event_type → internal handler)
    /api/v3/abha/validate
    /api/v3/consent/request
    /api/v3/bundle/push
    /api/v3/snomed/search
    ↓ (gateway proxies to)
ABDM M3 (https://dev.abdm.gov.in/api/v3)
CSNOtk (https://csnotk.e-atria.in/csnoserv)
```

## HMS CI4 Configuration (Already Updated)

**File:** `.env`

```env
# Now pointing to PHP gateway instead of CSNOtk
BRIDGE_SYNC_URL = https://abdm-bridge.e-atria.in/api/v1/bridge
BRIDGE_SYNC_TOKEN = <MUST_GENERATE_STRONG_TOKEN>  # Currently empty - see section below
BRIDGE_SOURCE_CODE = SBXID_033661
```

### Bearer Token Requirements

1. **Generate a strong random token** (minimum 32 characters, alphanumeric + special chars)
   - Example: `openssl rand -hex 32`
   - Result: `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2`

2. **Set this token in HMS CI4 .env:**
   ```env
   BRIDGE_SYNC_TOKEN = a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2
   ```

3. **Use the SAME token in PHP gateway .env:**
   ```env
   GATEWAY_BEARER_TOKEN = a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2
   ```

---

## PHP Gateway Setup on Ubuntu Server

### Prerequisites (to be verified on server)
- PHP 8.3+ with CLI and extensions (json, curl, pdo_mysql)
- MySQL 8.0+ (accessible locally)
- Apache 2.4 with mod_rewrite and `a2enmod ssl`
- Composer
- SSL certificate (Let's Encrypt recommended)

### Deployment Steps

#### 1. Prepare Server Directory
```bash
# SSH to https://abdm-bridge.e-atria.in server
ssh user@server-ip

# Create application directory
sudo mkdir -p /opt/abdm-gateway
sudo chown -R www-data:www-data /opt/abdm-gateway
cd /opt/abdm-gateway
```

#### 2. Clone/Copy PHP Gateway Code
```bash
# Option A: Copy from Windows (via SCP)
scp -r d:\Workplace\HMS_CI4_OLD\gateway-php-ci4/* user@server-ip:/opt/abdm-gateway/

# Option B: If on Linux, clone from repository
git clone <repo-url> /opt/abdm-gateway
```

#### 3. Install Dependencies
```bash
cd /opt/abdm-gateway
composer install --no-dev --optimize-autoloader
```

#### 4. Configure Environment
```bash
# Copy environment template
sudo cp .env.example .env
sudo chown www-data:www-data .env

# Edit .env with server credentials
sudo nano .env
```

**Critical .env settings:**
```env
# Database (must match HMS MySQL or create separate)
database.default.hostname = localhost
database.default.database = abdm_gateway_db
database.default.username = abdm_gw_user
database.default.password = <strong-password>

# ABDM Configuration
GATEWAY_BEARER_TOKEN = <SAME_TOKEN_AS_HMS>
ABDM_URL = https://dev.abdm.gov.in/api/v3
ABDM_TOKEN = 656f79f1-ef99-495f-9f37-713219ecbbcf
SNOMED_SERVICE_URL = https://csnotk.e-atria.in/csnoserv
GATEWAY_PUBLIC_URL = https://abdm-bridge.e-atria.in

# Facility IDs (from ABDM registration)
ABDM_HFR_ID = <your-hfr-id>
ABDM_NPI_ID = <your-npi-id>

# Security
GATEWAY_TEST_MODE = false
SESSION_COOKIE_SECURE = true
CI_ENVIRONMENT = production
```

#### 5. Generate Encryption Key
```bash
php spark key:generate
```

#### 6. Create Database & User
```bash
sudo mysql -u root -p -e "
CREATE DATABASE IF NOT EXISTS abdm_gateway_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'abdm_gw_user'@'localhost' IDENTIFIED BY '<strong-password>';
GRANT ALL PRIVILEGES ON abdm_gateway_db.* TO 'abdm_gw_user'@'localhost';
FLUSH PRIVILEGES;
"
```

#### 7. Run Migrations
```bash
cd /opt/abdm-gateway
php spark migrate
```

#### 8. Set Permissions
```bash
chmod -R 755 writable/
chmod -R 755 public/
sudo chown -R www-data:www-data writable/ public/ app/Database/Migrations/
```

#### 9. Configure Apache VirtualHost
```bash
# Use provided Apache config as template
sudo cp apache-vhost-abdm-bridge.conf /etc/apache2/sites-available/abdm-bridge.conf

# Enable site
sudo a2ensite abdm-bridge

# Test configuration
sudo apache2ctl configtest

# Reload Apache
sudo systemctl reload apache2
```

**Sample VirtualHost** (if not using provided config):
```apache
<VirtualHost *:443>
    ServerName abdm-bridge.e-atria.in
    DocumentRoot /opt/abdm-gateway/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/abdm-bridge.e-atria.in/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/abdm-bridge.e-atria.in/privkey.pem

    <Directory /opt/abdm-gateway/public>
        AllowOverride All
        Require all granted
        
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php/$1 [L]
        </IfModule>
    </Directory>

    <Directory /opt/abdm-gateway>
        <Files "app/*">
            Deny from all
        </Files>
    </Directory>

    ErrorLog /var/log/apache2/abdm-bridge-error.log
    CustomLog /var/log/apache2/abdm-bridge-access.log combined
</VirtualHost>
```

#### 10. Obtain SSL Certificate
```bash
# Using Certbot
sudo certbot certonly --apache -d abdm-bridge.e-atria.in

# Auto-renew
sudo systemctl enable certbot.timer
```

---

## Health Check & Smoke Tests

### 1. Health Endpoint (Public, no auth)
```bash
curl -i https://abdm-bridge.e-atria.in/api/v3/health
```

**Expected Response (200):**
```json
{
    "status": "ok",
    "timestamp": "2024-05-13T10:30:00+05:30",
    "service": "abdm-bridge-gateway",
    "version": "1.0.0",
    "mode": "live",
    "uptime": 3600
}
```

### 2. Gateway Status (Requires Bearer Token)
```bash
TOKEN="a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2"
curl -i -H "Authorization: Bearer $TOKEN" https://abdm-bridge.e-atria.in/api/v3/gateway/status
```

**Expected Response (200):**
```json
{
    "gateway": "operational",
    "database": "connected",
    "abdm_m3": "reachable",
    "snomed_service": "reachable"
}
```

### 3. Queue Processing from HMS
```bash
# SSH to HMS server
ssh user@hms-server

cd /opt/hms-ci4
# Process up to 5 queued events
php spark bridge:sync --limit 5
```

**Expected Output:**
```
Bridge sync completed:
- Processed: 5
- Sent: 5
- Failed: 0
- Message: queue processing completed
```

---

## Monitoring & Troubleshooting

### View Request Logs
```bash
# SSH to gateway server
mysql -u abdm_gw_user -p abdm_gateway_db

# Check recent API calls
SELECT request_id, endpoint, status_code, response_time_ms, created_at
FROM abdm_request_logs
ORDER BY created_at DESC
LIMIT 10;

# Check failed requests
SELECT * FROM abdm_request_logs
WHERE status_code >= 400
ORDER BY created_at DESC
LIMIT 5;
```

### View Audit Trail
```bash
# Track consent operations
SELECT request_id, action, patient_abha, action_status, details, created_at
FROM abdm_audit_trail
ORDER BY created_at DESC
LIMIT 10;

# Track bundle operations
SELECT * FROM abdm_bundles
WHERE push_status IN ('pending', 'failed')
ORDER BY created_at DESC;
```

### Debug PHP Gateway Logs
```bash
tail -f /var/log/apache2/abdm-bridge-error.log
tail -f /opt/abdm-gateway/writable/logs/log-*.log
```

### Common Issues & Fixes

**Issue: 403 Unauthorized on /api/v1/bridge**
```
Cause: Bearer token mismatch
Fix: Verify HMS BRIDGE_SYNC_TOKEN matches gateway GATEWAY_BEARER_TOKEN
     Check no trailing spaces in token value
```

**Issue: 500 Database Connection Error**
```
Cause: MySQL credentials incorrect or database not created
Fix: Verify .env database settings match actual MySQL setup
     Run: php spark migrate --group=default
```

**Issue: 404 on /api/v3/health**
```
Cause: Apache mod_rewrite not enabled or CI4 routing issue
Fix: sudo a2enmod rewrite && sudo systemctl reload apache2
     Check .htaccess exists in /opt/abdm-gateway/public/
```

**Issue: ABDM M3 Returns 503 (CloudFront)**
```
Cause: ABDM sandbox infrastructure issue
Status: Not under our control; usually temporary
Workaround: Enable test mode in .env (GATEWAY_TEST_MODE = true) for development
```

---

## Integration Checklist

- [ ] HMS CI4 .env updated with PHP gateway URL and bearer token
- [ ] PHP gateway code copied to /opt/abdm-gateway
- [ ] Composer dependencies installed (`composer install`)
- [ ] .env configured with database and ABDM credentials
- [ ] Encryption key generated (`php spark key:generate`)
- [ ] MySQL database created (`abdm_gateway_db`)
- [ ] Migrations run (`php spark migrate`)
- [ ] Permissions set on writable/ and public/ directories
- [ ] Apache VirtualHost configured with SSL
- [ ] DNS resolves abdm-bridge.e-atria.in to server IP
- [ ] Health check returns 200 OK
- [ ] Gateway status check returns operational
- [ ] Test: HMS `php spark bridge:sync --limit 1` processes queue
- [ ] Request logs populate in MySQL
- [ ] Bearer token documented in secure location

---

## Rollback Plan

If deployment fails:
1. Keep previous PHP gateway code backed up
2. If Apache fails: `sudo systemctl restart apache2`
3. If database corrupts: Restore from backup, re-run migrations
4. If token lost: Generate new token, update both HMS and gateway .env files
5. Revert HMS .env to old BRIDGE_SYNC_URL if needed (but keep updated for future use)

---

## References

- **PHP Gateway Code:** `d:\Workplace\HMS_CI4_OLD\gateway-php-ci4\`
- **HMS Integration:** `app/Libraries/BridgeSyncService.php` (buildBridgeDispatchContext method)
- **Queue Table:** `bridge_sync_queue` (HMS database)
- **Logging Tables:** `abdm_api_logs`, `abdm_audit_trail`, `abdm_bundles`, `abdm_request_logs` (gateway database)
- **Event Types Supported:** See Routes.php `/api/v1/bridge` handler documentation
