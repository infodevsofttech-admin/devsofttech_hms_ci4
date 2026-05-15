#!/bin/bash

# ABDM Gateway Automated Deployment Script for Ubuntu
# Run: sudo ./deploy.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}ABDM Bridge Gateway - Auto Deploy${NC}"
echo -e "${BLUE}========================================${NC}"

# Configuration
DOMAIN="abdm-bridge.e-atria.in"
APP_DIR="/opt/abdm-gateway"
APP_USER="gateway"
REPO_URL="${REPO_URL:-}"  # Set via: export REPO_URL="https://..."

if command -v docker-compose > /dev/null 2>&1; then
    COMPOSE_CMD="docker-compose"
else
    COMPOSE_CMD="docker compose"
fi

# Check root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ This script must be run as root${NC}"
   echo "Usage: sudo ./deploy.sh"
   exit 1
fi

# Step 1: System Update
echo -e "\n${YELLOW}[1/10] Updating system packages...${NC}"
apt-get update > /dev/null 2>&1
apt-get upgrade -y > /dev/null 2>&1
echo -e "${GREEN}✓ System updated${NC}"

# Step 2: Install Dependencies
echo -e "\n${YELLOW}[2/10] Installing dependencies...${NC}"
apt-get install -y curl wget git apt-transport-https ca-certificates gnupg lsb-release > /dev/null 2>&1
echo -e "${GREEN}✓ Dependencies installed${NC}"

# Step 3: Install Docker
echo -e "\n${YELLOW}[3/10] Installing Docker...${NC}"
if ! command -v docker &> /dev/null; then
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg 2>/dev/null
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
    apt-get update > /dev/null 2>&1
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin > /dev/null 2>&1
fi
echo -e "${GREEN}✓ Docker installed${NC}"

# Step 4: Install Certbot
echo -e "\n${YELLOW}[4/10] Installing Certbot for SSL...${NC}"
apt-get install -y certbot python3-certbot-nginx > /dev/null 2>&1
systemctl stop nginx 2>/dev/null || true
systemctl disable nginx 2>/dev/null || true
echo -e "${GREEN}✓ Certbot installed${NC}"

# Step 5: Create Application User
echo -e "\n${YELLOW}[5/10] Creating application user...${NC}"
if ! id -u $APP_USER > /dev/null 2>&1; then
    useradd -m -s /bin/bash $APP_USER
    usermod -aG docker $APP_USER
    echo -e "${GREEN}✓ User $APP_USER created${NC}"
else
    echo -e "${GREEN}✓ User $APP_USER already exists${NC}"
fi

# Step 6: Setup Application Directory
echo -e "\n${YELLOW}[6/10] Setting up application directory...${NC}"
mkdir -p $APP_DIR
if [ ! -z "$REPO_URL" ]; then
    cd $APP_DIR
    git clone $REPO_URL . 2>/dev/null || git pull origin main 2>/dev/null
else
    echo -e "${YELLOW}⚠ REPO_URL not set. Copy middleware files manually to $APP_DIR${NC}"
fi
mkdir -p $APP_DIR/logs
mkdir -p /var/www/certbot
chown -R $APP_USER:$APP_USER $APP_DIR
chmod -R 755 $APP_DIR
echo -e "${GREEN}✓ Application directory ready at $APP_DIR${NC}"

# Step 7: Configure Environment
echo -e "\n${YELLOW}[7/10] Configuring environment...${NC}"
if [ ! -f "$APP_DIR/.env" ]; then
    cp $APP_DIR/.env.example $APP_DIR/.env 2>/dev/null || echo "# Copy .env.example to .env and update values" > $APP_DIR/.env
    echo -e "${YELLOW}⚠ Please edit $APP_DIR/.env with your credentials${NC}"
else
    echo -e "${GREEN}✓ .env already configured${NC}"
fi

# Step 8: Get SSL Certificate
echo -e "\n${YELLOW}[8/10] Getting SSL certificate from Let's Encrypt...${NC}"
if [ ! -d "/etc/letsencrypt/live/$DOMAIN" ]; then
    echo "Enter your email for SSL certificate:"
    read -p "Email: " SSL_EMAIL
    certbot certonly --standalone -d $DOMAIN -m $SSL_EMAIL --agree-tos -n
    mkdir -p $APP_DIR/ssl
    cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem $APP_DIR/ssl/
    cp /etc/letsencrypt/live/$DOMAIN/privkey.pem $APP_DIR/ssl/
    chown -R $APP_USER:$APP_USER $APP_DIR/ssl
else
    echo -e "${GREEN}✓ SSL certificate already exists${NC}"
fi

# Step 9: Build and Start Containers
echo -e "\n${YELLOW}[9/10] Building and starting Docker containers...${NC}"
cd $APP_DIR
$COMPOSE_CMD build > /dev/null 2>&1
$COMPOSE_CMD up -d
sleep 5
if $COMPOSE_CMD ps | grep -q "abdm-bridge-gateway"; then
    echo -e "${GREEN}✓ Containers started successfully${NC}"
else
    echo -e "${RED}❌ Failed to start containers${NC}"
    $COMPOSE_CMD logs
    exit 1
fi

if curl -fsS "https://$DOMAIN/api/v3/health" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Health endpoint responding${NC}"
else
    echo -e "${YELLOW}⚠ Containers started but health endpoint did not return 200 yet${NC}"
fi

# Step 10: Setup Auto-Renewal and Systemd
echo -e "\n${YELLOW}[10/10] Setting up auto-renewal and systemd service...${NC}"

# Create renewal script
cat > /usr/local/bin/renew-ssl.sh << 'EOF'
#!/bin/bash
certbot renew --quiet
cp /etc/letsencrypt/live/abdm-bridge.e-atria.in/fullchain.pem /opt/abdm-gateway/ssl/
cp /etc/letsencrypt/live/abdm-bridge.e-atria.in/privkey.pem /opt/abdm-gateway/ssl/
cd /opt/abdm-gateway && if command -v docker-compose > /dev/null 2>&1; then docker-compose restart nginx > /dev/null 2>&1; else docker compose restart nginx > /dev/null 2>&1; fi
EOF
chmod +x /usr/local/bin/renew-ssl.sh

# Add crontab entry
(crontab -l 2>/dev/null | grep -v "renew-ssl.sh"; echo "0 2 * * * /usr/local/bin/renew-ssl.sh") | crontab -

# Create systemd service
cat > /etc/systemd/system/abdm-gateway.service << EOF
[Unit]
Description=ABDM Bridge Gateway
After=docker.service network-online.target
Requires=docker.service
Wants=network-online.target

[Service]
Type=simple
User=$APP_USER
WorkingDirectory=$APP_DIR
ExecStart=/bin/bash -lc 'cd $APP_DIR && if command -v docker-compose > /dev/null 2>&1; then docker-compose up; else docker compose up; fi'
ExecStop=/bin/bash -lc 'cd $APP_DIR && if command -v docker-compose > /dev/null 2>&1; then docker-compose down; else docker compose down; fi'
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable abdm-gateway > /dev/null 2>&1
echo -e "${GREEN}✓ Systemd service configured${NC}"

# Summary
echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}✅ Deployment Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Gateway URL:${NC} https://$DOMAIN"
echo -e "${BLUE}Health Check:${NC} curl https://$DOMAIN/api/v3/health"
echo ""
echo -e "${YELLOW}⚠ IMPORTANT:${NC}"
echo "1. Edit .env file with your credentials:"
echo "   sudo nano $APP_DIR/.env"
echo ""
echo "2. Restart gateway after updating .env:"
echo "   cd $APP_DIR && sudo $COMPOSE_CMD restart abdm-gateway"
echo ""
echo "3. Check logs:"
echo "   sudo docker logs abdm-bridge-gateway"
echo ""
echo "4. Service status:"
echo "   sudo systemctl status abdm-gateway"
echo ""
echo -e "${BLUE}Documentation:${NC} See $APP_DIR/DEPLOYMENT_GUIDE.md"
echo ""
