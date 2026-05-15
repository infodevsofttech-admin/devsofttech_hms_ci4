#!/bin/bash

# ABDM Gateway Middleware Setup on Ubuntu
# Script to deploy abdm-bridge.e-atria.in gateway server

set -e

echo "🚀 ABDM Middleware Gateway Setup for Ubuntu"
echo "==========================================="

# Check if running as root or sudo
if [[ $EUID -ne 0 ]]; then
   echo "❌ This script must be run as root (use: sudo ./setup.sh)"
   exit 1
fi

echo "📦 Updating system packages..."
apt-get update
apt-get upgrade -y

echo "📦 Installing Node.js and npm..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
apt-get install -y nodejs npm

echo "📦 Installing Docker and Docker Compose..."
apt-get install -y docker.io docker-compose curl wget

echo "🔧 Adding current user to docker group..."
usermod -aG docker $SUDO_USER

echo "📦 Installing Nginx..."
apt-get install -y nginx

echo "📦 Installing Certbot for SSL..."
apt-get install -y certbot python3-certbot-nginx

echo "✅ System dependencies installed!"
echo ""
echo "Next steps:"
echo "1. Copy middleware files to /opt/abdm-gateway/"
echo "2. Run: docker-compose up -d"
echo "3. Configure Nginx reverse proxy"
echo "4. Get SSL certificate with: certbot --nginx -d abdm-bridge.e-atria.in"
echo ""
echo "💡 See DEPLOYMENT_GUIDE.md for detailed instructions"
