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

# 1. Fix directory permissions
echo "[1/4] Fixing directory permissions..."
chown -R "$WEB_USER:$WEB_USER" "$HMS_DIR"
chmod -R 755 "$HMS_DIR"
chmod -R 755 "$HMS_DIR/.git"
echo "  Directory ownership set to $WEB_USER"

# 2. Set local git config (repository-specific, avoids /var/www/.gitconfig permission issues)
echo "[2/4] Setting git user name (local repository config)..."
cd "$HMS_DIR"
sudo -u "$WEB_USER" git config user.name "www-data"

# 3. Set git email (local repository config)
echo "[3/4] Setting git email (local repository config)..."
cd "$HMS_DIR"
sudo -u "$WEB_USER" git config user.email "deploy@localhost"

# 4. Test git status
echo "[4/4] Testing git status..."
cd "$HMS_DIR"
if sudo -u "$WEB_USER" git status >/dev/null 2>&1; then
  echo "✓ Git status check PASSED"
else
  echo "✗ Git status check FAILED"
  echo "  Output:"
  sudo -u "$WEB_USER" git status
  exit 1
fi

echo ""
echo "========================================="
echo "✓ Git setup completed successfully!"
echo "========================================="
echo ""
echo "Configuration stored in: $HMS_DIR/.git/config"
echo "Remote: $(cd $HMS_DIR && sudo -u $WEB_USER git config --get remote.origin.url)"
echo "Branch: main"
echo ""
echo "Next steps:"
echo "  1. Go to System Operations panel"
echo "  2. Click 'Update HMS' button"
echo "  3. Confirm the git pull action"
echo ""



