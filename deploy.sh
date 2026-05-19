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
REMOTE_CMDS="cd $PROJECT && git config core.fileMode false && git pull origin main && php spark migrate --namespace App && echo DEPLOY_OK"

if command -v plink &>/dev/null; then
    plink -ssh -batch -pw "$HMS_SSH_PASS" $SERVER "$REMOTE_CMDS"
else
    ssh -o StrictHostKeyChecking=no $SERVER "$REMOTE_CMDS"
fi

echo "=== Done ==="
