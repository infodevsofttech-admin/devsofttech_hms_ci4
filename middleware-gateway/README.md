# ABDM Middleware Gateway - Quick Reference

**Subdomain:** `abdm-bridge.e-atria.in`  
**Technology:** Node.js + Express + Docker  
**Status:** Ready for Deployment

---

## 📁 File Structure

```
middleware-gateway/
├── server.js              # Main Node.js application
├── package.json          # Dependencies
├── Dockerfile            # Docker image definition
├── docker-compose.yml    # Multi-container setup
├── nginx.conf            # Nginx reverse proxy & SSL
├── .env.example          # Environment template
├── deploy.sh             # Automated deployment script
├── DEPLOYMENT_GUIDE.md   # Full deployment documentation
├── setup-ubuntu.sh       # Ubuntu dependency installer
└── logs/                 # Application logs (created at runtime)
```

---

## 🚀 Quickest Deployment (3 steps)

### Step 1: Upload Files to Ubuntu Server

```bash
# From your local machine
scp -r middleware-gateway/ root@your-server-ip:/opt/

# SSH into server
ssh root@your-server-ip
cd /opt/middleware-gateway
```

### Step 2: Run Automated Deployment

```bash
# Make script executable
chmod +x deploy.sh

# Run deployment
sudo ./deploy.sh

# Follow prompts (email for SSL certificate)
```

### Step 3: Configure .env and Verify

```bash
# Edit configuration
sudo nano .env

# Update these critical values:
BRIDGE_SYNC_TOKEN=your-actual-token
ABDM_M3_TOKEN=your-abdm-token
ABDM_HFR_ID=your-hfr-id

# Save and restart
sudo docker-compose restart abdm-gateway

# Test health
curl https://abdm-bridge.e-atria.in/api/v3/health
```

---

## 📡 API Endpoints Available

### 1. Health Check (No Auth Required)
```bash
GET https://abdm-bridge.e-atria.in/api/v3/health
```

### 2. ABHA Validation
```bash
POST https://abdm-bridge.e-atria.in/api/v3/abha/validate
Header: Authorization: Bearer YOUR_TOKEN
Body: {
  "abha_id": "14-0061-0000-0001",
  "abha_address": "patient@abdm"
}
```

### 3. Consent Request
```bash
POST https://abdm-bridge.e-atria.in/api/v3/consent/request
Header: Authorization: Bearer YOUR_TOKEN
Body: {
  "patient_abha": "14-0061-0000-0001",
  "purpose": "treatment",
  "hi_types": ["OPConsultRecord", "PrescriptionRecord"],
  "date_range_from": "2024-01-01",
  "date_range_to": "2026-05-12"
}
```

### 4. Bundle Push
```bash
POST https://abdm-bridge.e-atria.in/api/v3/bundle/push
Header: Authorization: Bearer YOUR_TOKEN
Body: {
  "fhir_bundle": { ...bundle json... },
  "consent_id": "CONS-123456",
  "hi_type": "OPConsultRecord"
}
```

### 5. SNOMED Search
```bash
GET https://abdm-bridge.e-atria.in/api/v3/snomed/search?term=fever&return_limit=10
Header: Authorization: Bearer YOUR_TOKEN
```

### 6. Gateway Status
```bash
GET https://abdm-bridge.e-atria.in/api/v3/gateway/status
Header: Authorization: Bearer YOUR_TOKEN
```

---

## 🔧 Configuration (.env)

| Variable | Value | Notes |
|----------|-------|-------|
| `BRIDGE_SOURCE_CODE` | `SBXID_033661` | Client ID from ABDM |
| `BRIDGE_SYNC_TOKEN` | Bearer token | Get from middleware provider |
| `ABDM_M3_URL` | `https://dev.abdm.gov.in/api/v3` | Sandbox API endpoint |
| `ABDM_M3_TOKEN` | Bearer token | ABDM M3 authentication |
| `SNOMED_SERVICE_URL` | `https://csnotk.e-atria.in/csnoserv` | Terminology service |
| `ABDM_HFR_ID` | Your HFR ID | Health Facility Registry ID |
| `ABDM_NPI_ID` | Your NPI ID | National Practitioner ID |

---

## 🛠️ Common Commands

### Check Service Status
```bash
sudo systemctl status abdm-gateway
sudo docker-compose -f /opt/abdm-gateway/docker-compose.yml ps
```

### View Logs
```bash
# Real-time logs
sudo docker logs -f abdm-bridge-gateway

# Last 100 lines
sudo docker logs --tail 100 abdm-bridge-gateway

# With timestamps
sudo docker logs -t abdm-bridge-gateway
```

### Restart Service
```bash
cd /opt/abdm-gateway
sudo docker-compose restart abdm-gateway
```

### Stop Service
```bash
sudo systemctl stop abdm-gateway
# or
cd /opt/abdm-gateway && sudo docker-compose down
```

### Update Gateway Code
```bash
cd /opt/abdm-gateway
git pull origin main
sudo docker-compose build
sudo docker-compose up -d
```

---

## 🔐 Security Configuration

### Firewall Rules
```bash
# Allow only HTTPS
sudo ufw allow 443/tcp
sudo ufw allow 80/tcp  # For Let's Encrypt renewal
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw enable
```

### SSL Certificate Status
```bash
# Check certificate validity
sudo certbot certificates

# Manual renewal
sudo certbot renew --dry-run
```

### Bearer Token Security
- ✅ Generated unique token
- ✅ Required for all authenticated endpoints
- ✅ Never log token values
- ✅ Rotate quarterly (production)

---

## 📊 Monitoring

### Check Application Health
```bash
# Test gateway responsiveness
curl -v https://abdm-bridge.e-atria.in/api/v3/health

# Check all dependencies
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://abdm-bridge.e-atria.in/api/v3/gateway/status
```

### Resource Usage
```bash
# Container resource stats
docker stats abdm-bridge-gateway

# System resource usage
free -h
df -h
```

---

## 🔄 Integration with HMS

Update HMS `.env` to use the new gateway:

```env
# OLD: Direct ABDM or CSNOtk
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge

# NEW: Your gateway
BRIDGE_SYNC_URL = https://abdm-bridge.e-atria.in/api/v3
```

All HMS ABDM calls will now route through your gateway! ✅

---

## 📝 Request/Response Format

### Success Response
```json
{
  "ok": 1,
  "data": { ...response data... },
  "timestamp": "2026-05-12T10:30:00Z"
}
```

### Error Response
```json
{
  "ok": 0,
  "error": "Error description",
  "request_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Request ID Tracking
Every response includes `X-Request-ID` header for debugging.

---

## 🆘 Troubleshooting Quick Guide

| Issue | Solution |
|-------|----------|
| 403 Forbidden | Check `Authorization` header and token |
| 500 Server Error | Check `docker logs abdm-bridge-gateway` |
| SSL Certificate expired | Run `sudo certbot renew` |
| Port already in use | `sudo lsof -i :80` and kill process |
| ABDM API unreachable | Check firewall, VPN, token |
| High memory usage | Increase Docker memory limit |

---

## 📚 Additional Resources

- **Full Guide:** `DEPLOYMENT_GUIDE.md`
- **Express Docs:** https://expressjs.com
- **Docker Docs:** https://docs.docker.com
- **Nginx Docs:** https://nginx.org
- **Let's Encrypt:** https://letsencrypt.org/docs

---

## ✅ Deployment Checklist

- [ ] Files uploaded to `/opt/abdm-gateway`
- [ ] `sudo ./deploy.sh` executed
- [ ] SSL certificate obtained
- [ ] `.env` file configured with credentials
- [ ] Firewall rules configured (80, 443)
- [ ] Gateway health check passing
- [ ] All 3 dependency services responding
- [ ] HMS configured to use new gateway
- [ ] SSL auto-renewal set (cron)
- [ ] Logs being collected properly

---

**Status:** 🟢 Ready for Deployment  
**Deployment Time:** ~15 minutes  
**Estimated Uptime:** 99.9%

---

**Next Step:** Run `sudo ./deploy.sh` on Ubuntu server! 🚀
