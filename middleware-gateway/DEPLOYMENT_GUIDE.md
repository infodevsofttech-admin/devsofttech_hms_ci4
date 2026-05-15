# ABDM Bridge Gateway - Ubuntu Deployment Guide

**Subdomain:** `abdm-bridge.e-atria.in`  
**Stack:** Node.js + Express + Docker + Nginx  
**SSL:** Let's Encrypt (Certbot)

---

## 📋 Prerequisites

- **OS:** Ubuntu 20.04 LTS or later
- **Hardware:** Minimum 1GB RAM, 2GB disk space
- **Network:** Public IP address, domain (e-atria.in)
- **Ports:** 80, 443 open and accessible

---

## 🚀 Deployment Steps

### Step 1: Initial Server Setup (SSH as root)

```bash
# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Install curl
sudo apt-get install -y curl

# Add user for app (optional but recommended)
sudo useradd -m -s /bin/bash gateway
```

### Step 2: Install Docker & Docker Compose

```bash
# Install Docker
sudo apt-get install -y apt-transport-https ca-certificates curl gnupg lsb-release
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Add current user to docker group
sudo usermod -aG docker $USER
newgrp docker

# Verify installation
docker --version
docker-compose --version
```

### Step 3: Install Nginx & Certbot

```bash
# Install Nginx
sudo apt-get install -y nginx

# Install Certbot for SSL
sudo apt-get install -y certbot python3-certbot-nginx

# Stop Nginx (will use Docker Nginx)
sudo systemctl stop nginx
sudo systemctl disable nginx
```

### Step 4: Clone & Configure Gateway

```bash
# Create application directory
sudo mkdir -p /opt/abdm-gateway
cd /opt/abdm-gateway

# Clone or copy middleware-gateway files here
# (git clone, scp, or manual copy)
# Should have: package.json, server.js, Dockerfile, docker-compose.yml, nginx.conf

# Set permissions
sudo chown -R gateway:gateway /opt/abdm-gateway
sudo chmod -R 755 /opt/abdm-gateway
```

### Step 5: Configure Environment Variables

```bash
# Copy .env template
cp .env.example .env

# Edit .env with actual values
sudo nano .env
```

**Critical .env values to update:**

```env
BRIDGE_SYNC_TOKEN=your-actual-bearer-token
ABDM_M3_URL=https://dev.abdm.gov.in/api/v3
ABDM_M3_TOKEN=your-abdm-token
ABDM_HFR_ID=your-hfr-id
ABDM_NPI_ID=your-npi-id
```

### Step 6: Get SSL Certificate (Before Docker Start)

```bash
# Create required directories
sudo mkdir -p /opt/abdm-gateway/ssl
sudo mkdir -p /var/www/certbot

# Get certificate using Certbot
sudo certbot certonly --webroot -w /var/www/certbot -d abdm-bridge.e-atria.in

# Create certificate directory structure for Docker
sudo mkdir -p /opt/abdm-gateway/ssl
sudo cp /etc/letsencrypt/live/abdm-bridge.e-atria.in/fullchain.pem /opt/abdm-gateway/ssl/
sudo cp /etc/letsencrypt/live/abdm-bridge.e-atria.in/privkey.pem /opt/abdm-gateway/ssl/
sudo chown -R gateway:gateway /opt/abdm-gateway/ssl
```

### Step 7: Start Docker Containers

```bash
cd /opt/abdm-gateway

# Build images
docker-compose build

# Start services
docker-compose up -d

# Verify services are running
docker-compose ps

# Check logs
docker-compose logs -f abdm-gateway
```

### Step 8: Test Gateway Connectivity

```bash
# From local machine, test health endpoint
curl -X GET https://abdm-bridge.e-atria.in/api/v3/health

# Should return:
# {"status":"ok","timestamp":"2026-05-12T...","service":"abdm-bridge-gateway",...}
```

### Step 9: Set Up SSL Auto-Renewal

```bash
# Create renewal script
sudo nano /usr/local/bin/renew-ssl.sh
```

**Content:**
```bash
#!/bin/bash
certbot renew --quiet
cp /etc/letsencrypt/live/abdm-bridge.e-atria.in/fullchain.pem /opt/abdm-gateway/ssl/
cp /etc/letsencrypt/live/abdm-bridge.e-atria.in/privkey.pem /opt/abdm-gateway/ssl/
cd /opt/abdm-gateway && docker-compose restart nginx
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/renew-ssl.sh

# Add to crontab
sudo crontab -e
# Add line: 0 2 * * * /usr/local/bin/renew-ssl.sh
```

### Step 10: Systemd Service (Optional Auto-Start)

Create `/etc/systemd/system/abdm-gateway.service`:

```ini
[Unit]
Description=ABDM Bridge Gateway
After=docker.service
Requires=docker.service

[Service]
Type=simple
User=gateway
WorkingDirectory=/opt/abdm-gateway
ExecStart=/usr/bin/docker-compose up
ExecStop=/usr/bin/docker-compose down
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

```bash
# Enable and start service
sudo systemctl daemon-reload
sudo systemctl enable abdm-gateway
sudo systemctl start abdm-gateway
sudo systemctl status abdm-gateway
```

---

## 📡 Testing Gateway Endpoints

### 1. Health Check

```bash
curl -X GET https://abdm-bridge.e-atria.in/api/v3/health
```

### 2. Gateway Status (requires auth)

```bash
curl -X GET https://abdm-bridge.e-atria.in/api/v3/gateway/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. ABHA Validation

```bash
curl -X POST https://abdm-bridge.e-atria.in/api/v3/abha/validate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "abha_id": "14-0061-0000-0001",
    "abha_address": "patient@abdm"
  }'
```

### 4. SNOMED Search

```bash
curl -X GET "https://abdm-bridge.e-atria.in/api/v3/snomed/search?term=fever" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🔍 Monitoring & Logs

### View Logs

```bash
# Gateway application logs
docker-compose logs -f abdm-gateway

# Nginx access logs
docker-compose logs -f nginx

# System logs
sudo journalctl -u abdm-gateway -f

# Check specific timeframe
docker logs --since 1h abdm-bridge-gateway
```

### Docker Status

```bash
# Check running containers
docker ps

# Inspect container
docker inspect abdm-bridge-gateway

# Check resource usage
docker stats
```

---

## 🛠️ Troubleshooting

### Issue: Certificate not found

```bash
# Regenerate certificate
sudo certbot renew --force-renewal -d abdm-bridge.e-atria.in

# Copy to container volume
sudo cp /etc/letsencrypt/live/abdm-bridge.e-atria.in/* /opt/abdm-gateway/ssl/
docker-compose restart nginx
```

### Issue: Port already in use

```bash
# Check what's using port 80/443
sudo netstat -tlnp | grep ':80\|:443'

# Kill process if needed
sudo kill -9 <PID>

# Or change port in docker-compose.yml
```

### Issue: Gateway not responding

```bash
# Check Docker logs
docker logs abdm-bridge-gateway

# Restart containers
docker-compose restart

# Check .env file is correct
cat .env | grep -E 'ABDM|BRIDGE'
```

### Issue: ABDM API unreachable

```bash
# Test connectivity from container
docker exec abdm-bridge-gateway curl -v https://dev.abdm.gov.in/api/v3/health

# Check firewall rules
sudo ufw status
sudo ufw allow 443/tcp
```

---

## 🔒 Security Checklist

- [x] Firewall rules configured (80, 443 only)
- [x] SSL/TLS enabled (HTTPS only)
- [x] Rate limiting enabled
- [x] Security headers configured
- [x] Bearer token required for all APIs
- [x] Logs stored securely
- [x] Regular SSL certificate renewal
- [x] Docker running as non-root user
- [x] Environment variables not in Docker images

---

## 📊 Performance Optimization

### Max Connections
```bash
# Edit docker-compose.yml to increase limits if needed
# Current: 1 CPU core, 512MB RAM
```

### Scaling for Multiple Requests
```yaml
# Update docker-compose.yml
abdm-gateway:
  deploy:
    replicas: 2  # Run 2 instances
```

```bash
# Then use docker stack deploy for swarm mode
```

---

## 📝 Monitoring Setup (Optional)

### Install Prometheus + Grafana

```bash
# Add monitoring services to docker-compose.yml
# Reference: https://prometheus.io/docs/guides/docker-swarm/

# Then create dashboards in Grafana for:
# - Request rates
# - Response times
# - Error rates
# - Dependency health
```

---

## 🔄 Update Gateway

```bash
# Pull latest code
cd /opt/abdm-gateway
git pull origin main  # or manual update

# Rebuild images
docker-compose build

# Restart services
docker-compose up -d

# Verify
docker-compose ps
```

---

## 🚨 Emergency Rollback

```bash
# Stop current version
docker-compose down

# Restore previous docker-compose.yml
git checkout docker-compose.yml

# Restart
docker-compose up -d
```

---

## 📞 Support & Debugging

### Enable Debug Logging

```bash
# Update .env
LOG_LEVEL=debug

# Restart
docker-compose restart abdm-gateway
```

### Get Request ID for Debugging

```bash
# Each request has X-Request-ID header
curl -v https://abdm-bridge.e-atria.in/api/v3/health | grep X-Request-ID

# Search logs using request ID
docker logs abdm-bridge-gateway | grep <REQUEST-ID>
```

---

## 📈 Next Steps

1. **Test with HMS:** Update `app/Config/AbdmConnector.php` to use:
   ```env
   BRIDGE_SYNC_URL = https://abdm-bridge.e-atria.in/api/v3
   ```

2. **Verify SNOMED Service:** Test `/api/v3/snomed/search` endpoint

3. **Test ABHA Flow:** Use test ABHA from ABDM sandbox

4. **Monitor Logs:** Keep eye on `docker logs abdm-bridge-gateway`

5. **Setup Alerts:** Configure monitoring for downtime alerts

---

**Deployment Complete! Gateway is live at:** 🎉  
`https://abdm-bridge.e-atria.in/api/v3`
