# ABDM Integration Architecture - With Middleware Gateway

**Date:** May 12, 2026  
**Setup:** Two Gateway Options (CSNOtk vs Custom)

---

## 🏗️ System Architecture

```
┌─────────────────┐
│   HMS CI4       │
│  (PHP/CI4)      │
└────────┬────────┘
         │
         │ ABDM API Calls
         │
         ▼
    ┌────────────────────────────────────┐
    │  Middleware Gateway (Node.js)      │
    │  https://abdm-bridge.e-atria.in    │
    │  (Your Custom Gateway)             │
    └────────┬───────────┬─────┬─────────┘
             │           │     │
    ┌────────▼─┐  ┌──────▼──┐  │
    │  ABDM    │  │ SNOMED  │  │ (Alternative: CSNOtk Public)
    │ M3 API   │  │ Service │  │
    └──────────┘  └─────────┘  │
                                 │
                    ┌────────────▼────────────┐
                    │  CSNOtk Public Gateway  │
                    │  (csnotk.e-atria.in)    │
                    │  (Fallback Option)      │
                    └─────────────────────────┘
```

---

## 🔀 Two Gateway Options

### Option 1: CSNOtk Public Gateway (Current - No Setup Needed)

```
HMS → CSNOtk Gateway → ABDM M3 API
```

**Pros:**
- ✅ No infrastructure setup required
- ✅ Pre-configured and stable
- ✅ Shared bearer token approach

**Cons:**
- ❌ Shared infrastructure (potential bottleneck)
- ❌ No audit trail for your organization
- ❌ Limited customization
- ❌ Vendor dependency

**Configuration:**
```env
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge
```

---

### Option 2: Custom Middleware Gateway (New - Recommended for Production)

```
HMS → Your Gateway (abdm-bridge.e-atria.in) → ABDM M3 API
                              ↓
                        Your Infrastructure
```

**Pros:**
- ✅ Full control and customization
- ✅ Detailed logging and audit trail
- ✅ Custom rate limiting per client
- ✅ Advanced monitoring and alerting
- ✅ Dedicated infrastructure
- ✅ Better performance for your use case
- ✅ Future extensibility

**Cons:**
- ⚠️ Requires Ubuntu server setup
- ⚠️ Ongoing maintenance responsibility
- ⚠️ SSL certificate renewal

**Configuration:**
```env
BRIDGE_SYNC_URL = https://abdm-bridge.e-atria.in/api/v3
```

---

## 📋 Comparison Matrix

| Feature | CSNOtk Public | Your Gateway |
|---------|---------------|--------------|
| Setup Time | 5 min (edit .env) | 15 min (deploy script) |
| Audit Trail | Limited | Full audit logs |
| Rate Limiting | Global | Per-HMS instance |
| Performance | Shared | Dedicated |
| Monitoring | By CSNOtk | Full control |
| Customization | No | Full Node.js code |
| Cost | Included | Server cost only |
| SLA | Vendor SLA | Your responsibility |
| Latency | Depends on CSNOtk | Depends on your server |

---

## 🎯 Recommendation

**Use Your Gateway (Option 2) if:**
- ✅ Production deployment planned
- ✅ High transaction volume expected
- ✅ Compliance/audit trail required
- ✅ Want full control over integration
- ✅ Planning to scale beyond sandbox

**Use CSNOtk Public (Option 1) if:**
- ✅ Testing/sandbox only
- ✅ Low transaction volume
- ✅ Want zero infrastructure setup
- ✅ Short-term PoC

---

## 🚀 Deployment Scenarios

### Scenario 1: Quick Testing (This Week)
```
Step 1: Keep CSNOtk gateway
Step 2: Test OPD consult → SNOMED → ABHA → Consent
Step 3: Run full 7-step test sequence
Status: Fastest path to validation
```

### Scenario 2: Prepare for Production
```
Step 1: Deploy your gateway to Ubuntu server
        - Use deploy.sh for automation
        - Takes ~15 minutes
Step 2: Configure HMS to use your gateway
        - Edit .env: BRIDGE_SYNC_URL
Step 3: Run full test sequence again
Step 4: Monitor logs and performance
Status: Ready for production deployment
```

### Scenario 3: Hybrid (Recommended)
```
Phase 1 (Week 1):
  - Use CSNOtk for immediate testing
  - Complete 7-step validation
  
Phase 2 (Week 2):
  - Deploy your gateway in parallel
  - Run alongside CSNOtk
  
Phase 3 (Week 3):
  - Switch to your gateway
  - Keep CSNOtk as fallback
  
Phase 4 (Month 2+):
  - Production operations
  - Retire CSNOtk if desired
```

---

## 🔧 Switching Between Gateways

### From CSNOtk to Your Gateway

**Step 1:** Gateway already deployed at `abdm-bridge.e-atria.in`

**Step 2:** Update HMS `.env`

```bash
# Before
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge

# After
BRIDGE_SYNC_URL = https://abdm-bridge.e-atria.in/api/v3
```

**Step 3:** Restart HMS

```bash
# If using Docker
docker-compose restart hms-app

# Or PHP-FPM
sudo systemctl restart php-fpm
```

**Step 4:** Verify

```bash
# Test health
curl https://abdm-bridge.e-atria.in/api/v3/health

# Test OPD save
# Should work without any HMS code changes
```

---

## 📊 Request Flow Comparison

### Using CSNOtk Gateway
```
HMS
  └─ POST /api/v3/abha/validate
      └─ BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge
          └─ CSNOtk Gateway
              └─ ABDM M3 API
                  └─ Returns response
```

### Using Your Gateway
```
HMS
  └─ POST /api/v3/abha/validate
      └─ BRIDGE_SYNC_URL = https://abdm-bridge.e-atria.in/api/v3
          └─ Your Node.js Gateway
              └─ ABDM M3 API
                  └─ Returns response
              └─ Logs request to /logs/combined.log
              └─ Tracks metrics (response time, errors)
```

---

## 🛠️ Your Gateway Capabilities

Once deployed, your gateway provides:

### 1. Request/Response Logging
```bash
# Every API call logged with:
# - Request ID
# - Timestamp
# - Authorization status
# - Response time
# - Error details (if any)

sudo docker logs -f abdm-bridge-gateway | grep "ABHA validation"
```

### 2. Performance Monitoring
```bash
# Response times for each endpoint
docker stats abdm-bridge-gateway

# Memory usage
free -h
```

### 3. Custom Metrics
- Total requests per hour
- ABHA validations succeeded/failed
- Average response time
- Error rates by endpoint

### 4. Security Features
- Rate limiting (100 req/15min per IP)
- Bearer token authentication
- Request ID tracking
- SSL/TLS encryption
- CORS policy

### 5. Future Extensibility
- Add JWT token support
- Implement caching layer
- Add request signing
- Custom consent workflows
- FHIR bundle transformation

---

## 📈 Performance Expectations

### CSNOtk Gateway
- **Avg Response Time:** 500-1000ms
- **Rate Limit:** Shared with all users
- **Latency:** Depends on CSNOtk load

### Your Gateway (Dedicated Server)
- **Avg Response Time:** 100-300ms (faster)
- **Rate Limit:** 100 per 15 min (per HMS instance)
- **Latency:** Controlled infrastructure

---

## 🔐 Security Considerations

### Token Management
```
CSNOtk:     Shared bearer token
            └─ Single point of failure
            └─ Limited audit trail

Your Gateway: Individual tokens per service
             └─ Rotate quarterly
             └─ Full audit trail
             └─ Can revoke per-HMS
```

### Audit Trail
```
CSNOtk:     Minimal logging
            └─ No visibility into errors
            └─ Compliance unclear

Your Gateway: Complete logging
             └─ Every request tracked
             └─ Full error details
             └─ Compliance-ready
```

---

## 💰 Cost Comparison

### CSNOtk Public (Option 1)
- **Server Cost:** $0 (shared)
- **Maintenance:** Vendor responsibility
- **Setup Time:** 5 min
- **Annual Cost:** $0

### Your Gateway (Option 2)
- **Server Cost:** $10-50/month (Ubuntu VPS)
- **Maintenance:** Your responsibility
- **Setup Time:** 15 min (automated)
- **Annual Cost:** $120-600

**ROI:** Worth it for production; skip for PoC

---

## 🎓 Implementation Roadmap

```
Week 1: Testing Phase
├─ Use CSNOtk gateway
├─ Complete 7-step test sequence
└─ Validate ABDM integration works

Week 2: Production Prep
├─ Deploy your gateway (15 min)
├─ Test both gateways in parallel
├─ Verify identical responses
└─ Performance benchmark

Week 3: Migration
├─ Switch to your gateway
├─ Keep CSNOtk as fallback
├─ Monitor for issues
└─ Document any differences

Week 4+: Production
├─ Run on your gateway only
├─ Setup monitoring/alerting
├─ Prepare disaster recovery
└─ Plan scaling strategy
```

---

## ✅ Decision Matrix

### Quick Decision Tree

```
Question 1: Is this production?
├─ YES  → Deploy your gateway → Option 2
└─ NO   → Continue with CSNOtk → Option 1

Question 2: High transaction volume expected?
├─ YES (>1000/day) → Your gateway → Option 2
└─ NO  (<1000/day) → CSNOtk OK   → Option 1

Question 3: Compliance/audit requirements?
├─ YES → Must have your gateway → Option 2
└─ NO  → CSNOtk sufficient       → Option 1

Question 4: Budget for infrastructure?
├─ YES → Deploy your gateway → Option 2
└─ NO  → Use CSNOtk for now  → Option 1
```

---

## 📞 Making the Switch

### If You Decide to Deploy Your Gateway Later

1. **Create Ubuntu Server**
   ```bash
   VPS provider: DigitalOcean, Linode, AWS, etc.
   OS: Ubuntu 20.04+ 
   Size: 1GB RAM, 25GB SSD
   ```

2. **Copy Files**
   ```bash
   scp -r middleware-gateway/ root@SERVER_IP:/opt/
   ```

3. **Run Deploy Script**
   ```bash
   ssh root@SERVER_IP
   cd /opt/middleware-gateway
   sudo ./deploy.sh
   ```

4. **Update HMS .env**
   ```bash
   BRIDGE_SYNC_URL = https://abdm-bridge.e-atria.in/api/v3
   ```

5. **Restart HMS & Test**
   Done! Fully automatic upgrade path.

---

## 🎯 Recommendation for Your Project

### Current Status: May 12, 2026
- ✅ ABDM credentials ready (SBXID_033661)
- ✅ OPD form ABDM-compliant
- ✅ CSNOtk gateway accessible
- ✅ Your gateway code ready to deploy

### Immediate Next (This Week)
```
Use CSNOtk Gateway:
1. Quick testing of OPD → ABDM flow
2. Validate SNOMED integration
3. Complete 7-step test sequence
Duration: 2-3 days
```

### Future (Production Ready)
```
Deploy Your Gateway:
1. Use automated deploy.sh
2. Takes 15 minutes
3. Seamless switch for HMS
4. Full control + audit trail
Recommended: Before live usage
```

---

**Status:** 🟢 Both options ready  
**Quick Start:** CSNOtk (no setup)  
**Production Ready:** Your Gateway (15 min setup)  
**Recommendation:** Start with CSNOtk, upgrade to your gateway in Week 2

---

## 📚 Next Steps

1. **This Week:** Use CSNOtk, run tests, validate ABDM integration
2. **Next Week:** Review this document, decide on production path
3. **Before Go-Live:** Deploy your gateway using `deploy.sh`
4. **Post-Go-Live:** Monitor, optimize, scale as needed

---

**Questions?** Reference the detailed guides:
- Quick Start: `docs/ABDM_QUICK_START.md`
- Full Deployment: `middleware-gateway/DEPLOYMENT_GUIDE.md`
- Custom Gateway: `middleware-gateway/README.md`
