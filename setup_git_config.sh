#!/bin/bash
# Setup script to configure git for www-data user
# For PUBLIC repositories - no authentication required
# Run this on your live server as root to fix git pull issues

set -e

echo "========================================="
echo "HMS Git Configuration (Public Repo)"
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
echo "  Repo type: PUBLIC (no authentication needed)"
echo ""

# 1. Fix global git config file permissions
echo "[1/5] Fixing .gitconfig file..."

# Make sure /var/www is writable by www-data
chmod 755 /var/www

# Remove corrupted .gitconfig if it exists
[ -f /var/www/.gitconfig ] && rm -f /var/www/.gitconfig

# Create fresh .gitconfig file and set permissions BEFORE writing
sudo -u "$WEB_USER" touch /var/www/.gitconfig
sudo -u "$WEB_USER" chmod 600 /var/www/.gitconfig

echo "  Fresh .gitconfig created with www-data ownership"

# 2. Set minimal git config (name and email required for git)
echo "[2/5] Setting git user name..."
if ! sudo -u "$WEB_USER" git config --global user.name "www-data" 2>/dev/null; then
  echo "  Warning: Could not set global config, using local repo config instead"
  cd "$HMS_DIR"
  sudo -u "$WEB_USER" git config user.name "www-data"
fi

# 3. Set git email
echo "[3/5] Setting git email..."
if ! sudo -u "$WEB_USER" git config --global user.email "deploy@localhost" 2>/dev/null; then
  echo "  Warning: Could not set global config, using local repo config instead"
  cd "$HMS_DIR"
  sudo -u "$WEB_USER" git config user.email "deploy@localhost"
fi

# 4. Fix directory permissions
echo "[4/5] Fixing directory permissions..."
chown -R "$WEB_USER:$WEB_USER" "$HMS_DIR"
chmod -R 755 "$HMS_DIR"
chmod -R 755 "$HMS_DIR/.git"

# 5. Test git status
echo "[5/5] Testing git pull..."
cd "$HMS_DIR"
if sudo -u "$WEB_USER" git status >/dev/null 2>&1; then
  echo "✓ Git status check PASSED"
else
  echo "✗ Git status check FAILED"
  sudo -u "$WEB_USER" git status
  exit 1
fi

echo ""
echo "========================================="
echo "✓ Git setup completed successfully!"
echo "========================================="
echo ""
echo "Next steps:"
echo "  1. Go to System Operations panel"
echo "  2. Click 'Update HMS' button"
echo "  3. Confirm the git pull action"
echo ""
echo "Git configuration:"
echo "  Global: /var/www/.gitconfig"
echo "  Remote: $(cd $HMS_DIR && sudo -u $WEB_USER git config --get remote.origin.url)"
echo "  Branch: main"
echo ""


