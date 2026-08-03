#!/bin/bash
# Setup script to configure git for www-data user
# Run this on your live server as root to fix git pull issues

set -e

echo "========================================="
echo "HMS Git Configuration Setup Script"
echo "========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
  echo "ERROR: This script must be run as root"
  echo "Usage: sudo bash setup_git_config.sh"
  exit 1
fi

# Determine web server user
WEB_USER="www-data"
if ! id "$WEB_USER" &>/dev/null; then
  echo "ERROR: User '$WEB_USER' not found. Please adjust WEB_USER variable."
  exit 1
fi

# HMS installation directory
HMS_DIR="/var/www/html/hms_etria"
if [ ! -d "$HMS_DIR" ]; then
  echo "ERROR: HMS directory not found at $HMS_DIR"
  echo "Please update HMS_DIR in this script"
  exit 1
fi

echo "Configuration Details:"
echo "  Web user: $WEB_USER"
echo "  HMS dir: $HMS_DIR"
echo ""

# 1. Create .ssh directory for www-data if it doesn't exist
echo "[1/7] Creating .ssh directory for $WEB_USER..."
sudo -u "$WEB_USER" mkdir -p /var/www/.ssh
sudo -u "$WEB_USER" chmod 700 /var/www/.ssh

# 2. Configure git user name
echo "[2/7] Configuring git user name..."
sudo -u "$WEB_USER" git config --global user.name "HMS Deploy User"

# 3. Configure git email
echo "[3/7] Configuring git email..."
sudo -u "$WEB_USER" git config --global user.email "deploy@hms.local"

# 4. Disable SSL verification (for HTTPS repos)
echo "[4/7] Configuring git SSL settings..."
sudo -u "$WEB_USER" git config --global http.sslVerify false

# 5. Set git to use HTTPS instead of SSH (easier for automated deploys)
echo "[5/7] Configuring git to use HTTPS..."
cd "$HMS_DIR"
sudo -u "$WEB_USER" git remote set-url origin https://github.com/infodevsofttech-admin/devsofttech_hms_ci4.git

# 6. Fix directory permissions
echo "[6/7] Fixing directory permissions..."
chown -R "$WEB_USER:$WEB_USER" "$HMS_DIR"
chmod -R 755 "$HMS_DIR"
chmod -R 755 "$HMS_DIR/.git"

# 7. Test git status
echo "[7/7] Testing git status..."
cd "$HMS_DIR"
if sudo -u "$WEB_USER" git status &>/dev/null; then
  echo "✓ Git status check PASSED"
else
  echo "✗ Git status check FAILED"
  exit 1
fi

echo ""
echo "========================================="
echo "✓ Git configuration completed successfully!"
echo "========================================="
echo ""
echo "You can now use the 'Update HMS' button in System Operations panel"
echo ""
echo "Git configuration stored at:"
echo "  Global: /var/www/.gitconfig"
echo "  Local:  $HMS_DIR/.git/config"
echo ""
echo "To verify configuration, run:"
echo "  sudo -u www-data git config --list"
echo ""
