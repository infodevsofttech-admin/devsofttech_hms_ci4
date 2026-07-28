#!/usr/bin/env bash
# deploy.sh — Push local changes and deploy to production server
# Usage: bash deploy.sh "commit message"
# Requires: PuTTY plink at default location (Windows) or ssh (Linux/Mac)
#
# Server: 159.89.175.190  Project: /var/www/html/hms_etria

set -e

MSG="${1:-Auto-deploy $(date '+%Y-%m-%d %H:%M')}"
SERVER="root@159.89.175.190"
PROJECT="/var/www/html/hms_etria"

echo "=== Staging and committing ==="
git add -A
# Don't commit env (server has its own)
git reset HEAD env 2>/dev/null || true
git diff --cached --stat
git commit -m "$MSG" 2>/dev/null || echo "(nothing to commit)"

echo "=== Pushing to GitHub ==="
git push origin main

echo "=== Deploying to server ==="
# NOTE: this server's default `php` CLI is NOT the app-compatible PHP version;
# use `php83` explicitly (resolved to its absolute path on the server) for both
# the migration step and the cron installer, otherwise cron jobs silently fail
# with "syntax error, unexpected ')'" parse errors from an incompatible PHP CLI.
REMOTE_CMDS="cd $PROJECT && git config core.fileMode false && git pull origin main && PHP_BIN=\$(command -v php83 || command -v php) && \$PHP_BIN spark migrate --namespace App && PHP_BIN=\$PHP_BIN bash scripts/install_hms_cron.sh $PROJECT && echo DEPLOY_OK"

if command -v plink &>/dev/null; then
    plink -ssh -batch -pw "$HMS_SSH_PASS" $SERVER "$REMOTE_CMDS"
else
    ssh -o StrictHostKeyChecking=no $SERVER "$REMOTE_CMDS"
fi

echo "=== Done ==="
