#!/usr/bin/env bash
# ==============================================================================
# E-Atria HMS (Hospital Management System) - Ubuntu Auto-Installer
# ==============================================================================
# This script automates the complete installation of HMS CI4 on Ubuntu:
#   1. System packages (PHP 8.3, Apache2, MySQL/MariaDB, Composer, Git)
#   2. Apache modules (rewrite, ssl, headers)
#   3. Target application cloning/copying & permission setup
#   4. Environment (.env) configuration
#   5. Database initialization (Schema + Masters + Templates via Starter SQL)
#   6. Admin user & Hospital setting configuration via 'php spark hms:install'
#   7. Apache VirtualHost & SSL configuration
# ==============================================================================

set -euo pipefail

# Text colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

echo -e "${CYAN}${BOLD}"
echo "======================================================================"
echo "          E-Atria Hospital Management System (HMS CI4)                "
echo "                   Ubuntu Installation Script                         "
echo "======================================================================"
echo -e "${NC}"

# Check root/sudo
if [ "$(id -u)" -ne 0 ]; then
    echo -e "${RED}[ERROR] This script must be run as root or with sudo.${NC}"
    exit 1
fi

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS_NAME=$NAME
    OS_VER=$VERSION_ID
else
    OS_NAME="Linux"
    OS_VER="Unknown"
fi
echo -e "${GREEN}[INFO] Detected OS: ${OS_NAME} (${OS_VER})${NC}"

# Configuration Variables (Defaults can be overridden via environment or interactive prompts)
DOMAIN="${1:-${DOMAIN:-}}"
DB_NAME="${2:-${DB_NAME:-}}"
HOSPITAL_NAME="${3:-${HOSPITAL_NAME:-}}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
ADMIN_PASS="${ADMIN_PASS:-Admin@12345}"
REPO_URL="${REPO_URL:-https://git.e-atria.net/d_s_bisht/devsofttech_hms_ci4.git}"
SSL_CERT="${SSL_CERT:-/etc/letsencrypt/live/e-atria.net/fullchain.pem}"
SSL_KEY="${SSL_KEY:-/etc/letsencrypt/live/e-atria.net/privkey.pem}"

# Interactive prompts if arguments not provided
if [ -z "$DOMAIN" ]; then
    read -rp "Enter Domain Name (e.g. johrihospital.e-atria.net): " DOMAIN
fi

if [ -z "$DB_NAME" ]; then
    read -rp "Enter Database Name (e.g. hms_johri_ss_hospital): " DB_NAME
fi

if [ -z "$HOSPITAL_NAME" ]; then
    read -rp "Enter Hospital Name (e.g. Johri Hospital): " HOSPITAL_NAME
fi

if [ -z "$DB_PASS" ]; then
    read -rsp "Enter MySQL Password for user '$DB_USER': " DB_PASS
    echo ""
fi

INSTALL_DIR="/var/www/html/eatria_client/${DOMAIN%%.*}"
if [ -d "/var/www/html/${DOMAIN%%.*}" ]; then
    INSTALL_DIR="/var/www/html/${DOMAIN%%.*}"
fi

echo -e "\n${YELLOW}${BOLD}Configuration Summary:${NC}"
echo "  Domain Name    : $DOMAIN"
echo "  Target Dir     : $INSTALL_DIR"
echo "  Database Name  : $DB_NAME"
echo "  Database User  : $DB_USER"
echo "  Hospital Name  : $HOSPITAL_NAME"
echo "  Admin User     : $ADMIN_USER"
echo "  Admin Password : [HIDDEN]"
echo ""

# ------------------------------------------------------------------------------
# Step 1: Install System Prerequisites
# ------------------------------------------------------------------------------
echo -e "${CYAN}--> Step 1: Checking and installing system packages...${NC}"
export DEBIAN_FRONTEND=noninteractive

apt-get update -y -q

# Install software-properties-common if missing
apt-get install -y -q software-properties-common curl git unzip ufw ca-certificates

# Add ondrej/php PPA if PHP 8.3 not available
if ! command -v php8.3 &>/dev/null; then
    echo -e "${YELLOW}Adding ondrej/php repository for PHP 8.3...${NC}"
    add-apt-repository -y ppa:ondrej/php
    apt-get update -y -q
fi

# Install Apache, MySQL client, PHP 8.3 and required extensions
apt-get install -y -q \
    apache2 \
    mysql-client \
    php8.3 \
    php8.3-cli \
    php8.3-fpm \
    php8.3-mysql \
    php8.3-curl \
    php8.3-gd \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-intl \
    php8.3-zip \
    php8.3-bcmath

# Enable Apache modules
echo -e "${CYAN}--> Enabling required Apache modules...${NC}"
a2enmod rewrite ssl headers proxy proxy_fcgi setenvif -q

# Check Composer
if ! command -v composer &>/dev/null; then
    echo -e "${YELLOW}Installing Composer...${NC}"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# ------------------------------------------------------------------------------
# Step 2: Clone or Copy Application Files
# ------------------------------------------------------------------------------
echo -e "${CYAN}--> Step 2: Setting up application in ${INSTALL_DIR}...${NC}"
mkdir -p "$(dirname "$INSTALL_DIR")"

if [ ! -d "$INSTALL_DIR/.git" ]; then
    echo -e "${YELLOW}Cloning repository from ${REPO_URL}...${NC}"
    git clone "$REPO_URL" "$INSTALL_DIR"
else
    echo -e "${GREEN}Repository already cloned. Pulling latest changes...${NC}"
    cd "$INSTALL_DIR"
    git pull origin main || true
fi

cd "$INSTALL_DIR"

# Install/Copy vendor dependencies
if [ ! -d "$INSTALL_DIR/vendor" ]; then
    if [ -d "/var/www/html/hms_etria/vendor" ]; then
        echo -e "${YELLOW}Copying pre-installed vendor libraries from hms_etria...${NC}"
        cp -a /var/www/html/hms_etria/vendor "$INSTALL_DIR/vendor"
    else
        echo -e "${YELLOW}Running composer install...${NC}"
        composer install --no-dev --optimize-autoloader
    fi
fi

# Ensure all writable subdirectories exist
mkdir -p \
    "$INSTALL_DIR/writable/cache" \
    "$INSTALL_DIR/writable/logs" \
    "$INSTALL_DIR/writable/session" \
    "$INSTALL_DIR/writable/uploads" \
    "$INSTALL_DIR/writable/debugbar" \
    "$INSTALL_DIR/public/assets/images"

# Set permissions
chown -R www-data:www-data "$INSTALL_DIR"
chmod -R 775 "$INSTALL_DIR/writable" "$INSTALL_DIR/public/assets/images"

# ------------------------------------------------------------------------------
# Step 3: Setup Environment (.env)
# ------------------------------------------------------------------------------
echo -e "${CYAN}--> Step 3: Configuring .env...${NC}"
cat <<EOF > "$INSTALL_DIR/.env"
CI_ENVIRONMENT = production

app.baseURL = 'https://${DOMAIN}/'

database.default.hostname = ${DB_HOST}
database.default.username = ${DB_USER}
database.default.password = ${DB_PASS}
database.default.database = ${DB_NAME}
database.default.DBDriver = MySQLi
database.default.port = ${DB_PORT}
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

chown www-data:www-data "$INSTALL_DIR/.env"
chmod 640 "$INSTALL_DIR/.env"

# ------------------------------------------------------------------------------
# Step 4: Setup Database & Run Spark Installer
# ------------------------------------------------------------------------------
echo -e "${CYAN}--> Step 4: Ensuring Database and running HMS Installer...${NC}"

# Create DB if it doesn't exist
MYSQL_CMD="mysql -h ${DB_HOST} -P ${DB_PORT} -u ${DB_USER}"
if [ -n "$DB_PASS" ]; then
    MYSQL_CMD="${MYSQL_CMD} -p${DB_PASS}"
fi

$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" || {
    echo -e "${RED}[ERROR] Could not create database '${DB_NAME}'. Check MySQL credentials.${NC}"
    exit 1
}

# Run CodeIgniter 4 HMS installer command
php spark hms:install \
    --hospital-name="${HOSPITAL_NAME}" \
    --admin-user="${ADMIN_USER}" \
    --admin-email="${ADMIN_EMAIL}" \
    --admin-password="${ADMIN_PASS}"

# ------------------------------------------------------------------------------
# Step 5: Configure Apache VirtualHost & SSL
# ------------------------------------------------------------------------------
echo -e "${CYAN}--> Step 5: Setting up Apache VirtualHost for ${DOMAIN}...${NC}"

VHOST_FILE="/etc/apache2/sites-available/${DOMAIN}.conf"

# Check if SSL certificates exist
HAS_SSL=false
if [ -f "$SSL_CERT" ] && [ -f "$SSL_KEY" ]; then
    HAS_SSL=true
fi

if [ "$HAS_SSL" = true ]; then
    echo -e "${GREEN}Using existing SSL certificate (${SSL_CERT})...${NC}"
    cat <<EOF > "$VHOST_FILE"
<VirtualHost *:80>
    ServerName ${DOMAIN}
    Redirect permanent / https://${DOMAIN}/
</VirtualHost>

<VirtualHost *:443>
    ServerName ${DOMAIN}

    SSLEngine on
    SSLCertificateFile ${SSL_CERT}
    SSLCertificateKeyFile ${SSL_KEY}

    DocumentRoot ${INSTALL_DIR}/public

    <Directory ${INSTALL_DIR}/public>
        Options +FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html index.htm

        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php/\$1 [L]
        </IfModule>
    </Directory>

    ErrorLog /var/log/apache2/${DOMAIN}_error.log
    CustomLog /var/log/apache2/${DOMAIN}_access.log combined
</VirtualHost>
EOF
else
    echo -e "${YELLOW}SSL cert not found at ${SSL_CERT}. Creating standard HTTP virtualhost...${NC}"
    cat <<EOF > "$VHOST_FILE"
<VirtualHost *:80>
    ServerName ${DOMAIN}
    DocumentRoot ${INSTALL_DIR}/public

    <Directory ${INSTALL_DIR}/public>
        Options +FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html index.htm

        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php/\$1 [L]
        </IfModule>
    </Directory>

    ErrorLog /var/log/apache2/${DOMAIN}_error.log
    CustomLog /var/log/apache2/${DOMAIN}_access.log combined
</VirtualHost>
EOF
fi

# Enable site
a2ensite "${DOMAIN}.conf"
apache2ctl configtest
systemctl reload apache2

# ------------------------------------------------------------------------------
# Step 6: Verify Endpoint
# ------------------------------------------------------------------------------
echo -e "${CYAN}--> Step 6: Testing website endpoint...${NC}"
HTTP_CODE=$(curl -k -s -o /dev/null -w "%{http_code}" "https://${DOMAIN}/login" || true)

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}${BOLD}[SUCCESS] HMS is successfully installed and responding (HTTP 200)!${NC}"
else
    echo -e "${YELLOW}[NOTE] HTTP Code: ${HTTP_CODE}. Please verify Apache logs at /var/log/apache2/${DOMAIN}_error.log if needed.${NC}"
fi

echo -e "\n${GREEN}${BOLD}======================================================================${NC}"
echo -e "${GREEN}${BOLD}                   INSTALLATION COMPLETE!                             ${NC}"
echo -e "${GREEN}${BOLD}======================================================================${NC}"
echo -e "  Web URL        : https://${DOMAIN}/login"
echo -e "  Hospital Name  : ${HOSPITAL_NAME}"
echo -e "  Database       : ${DB_NAME}"
echo -e "  Admin Username : ${ADMIN_USER}"
echo -e "  Admin Password : ${ADMIN_PASS}"
echo -e "  Install Path   : ${INSTALL_DIR}"
echo -e "${GREEN}======================================================================${NC}\n"
