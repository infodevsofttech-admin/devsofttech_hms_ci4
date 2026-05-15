import express from 'express';
import axios from 'axios';
import dotenv from 'dotenv';
import helmet from 'helmet';
import cors from 'cors';
import morgan from 'morgan';
import rateLimit from 'express-rate-limit';
import { v4 as uuidv4 } from 'uuid';
import compression from 'compression';
import winston from 'winston';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const logsDir = path.join(__dirname, 'logs');

// Ensure log directory exists before Winston file transports initialize.
if (!fs.existsSync(logsDir)) {
  fs.mkdirSync(logsDir, { recursive: true });
}

// Initialize Express App
const app = express();
const PORT = process.env.PORT || 3000;
const NODE_ENV = process.env.NODE_ENV || 'development';

// Logger Setup
const logger = winston.createLogger({
  level: process.env.LOG_LEVEL || 'info',
  format: winston.format.json(),
  defaultMeta: { service: 'abdm-bridge-gateway' },
  transports: [
    new winston.transports.File({ filename: path.join(logsDir, 'error.log'), level: 'error' }),
    new winston.transports.File({ filename: path.join(logsDir, 'combined.log') }),
  ],
});

if (NODE_ENV !== 'production') {
  logger.add(new winston.transports.Console({
    format: winston.format.simple(),
  }));
}

// Middleware
app.use(helmet()); // Security headers
app.use(compression()); // GZIP compression
app.use(cors()); // CORS
app.use(morgan('combined', { stream: { write: msg => logger.info(msg) } })); // Request logging
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ limit: '10mb', extended: true }));

// Rate Limiting
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100, // limit each IP to 100 requests per windowMs
  message: 'Too many requests from this IP, please try again later.',
});
app.use('/api/', limiter);

// Authentication Middleware
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    logger.warn('Missing authorization token', { ip: req.ip });
    return res.status(401).json({ ok: 0, error: 'Missing authorization token' });
  }

  const expectedToken = process.env.BRIDGE_SYNC_TOKEN;
  if (token !== expectedToken) {
    logger.warn('Invalid authorization token', { ip: req.ip });
    return res.status(403).json({ ok: 0, error: 'Invalid authorization token' });
  }

  next();
};

// Request ID Middleware
app.use((req, res, next) => {
  req.id = uuidv4();
  res.setHeader('X-Request-ID', req.id);
  next();
});

// ============================================
// ABDM Gateway Endpoints
// ============================================

/**
 * Health Check Endpoint
 * GET /api/v3/health
 */
app.get('/api/v3/health', (req, res) => {
  logger.info('Health check requested', { requestId: req.id });
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString(),
    service: 'abdm-bridge-gateway',
    version: '1.0.0',
    uptime: process.uptime(),
  });
});

/**
 * ABHA Validation Endpoint
 * POST /api/v3/abha/validate
 * Proxies ABHA validation to NHA ABDM M3 API
 */
app.post('/api/v3/abha/validate', authenticateToken, async (req, res) => {
  try {
    const { abha_id, abha_address } = req.body;

    logger.info('ABHA validation request', { 
      requestId: req.id, 
      abha_id: abha_id ? abha_id.substring(0, 5) + '***' : 'null' 
    });

    if (!abha_id && !abha_address) {
      return res.status(400).json({ 
        ok: 0, 
        error: 'Either abha_id or abha_address required' 
      });
    }

    // Call ABDM M3 API
    const abdmResponse = await axios.post(
      `${process.env.ABDM_M3_URL}/abha/validate`,
      {
        abha_id: abha_id,
        abha_address: abha_address,
        request_id: req.id,
      },
      {
        headers: {
          'Authorization': `Bearer ${process.env.ABDM_M3_TOKEN}`,
          'Content-Type': 'application/json',
          'X-Client-ID': process.env.BRIDGE_SOURCE_CODE,
        },
        timeout: parseInt(process.env.BRIDGE_SYNC_TIMEOUT || '30') * 1000,
      }
    );

    logger.info('ABHA validation successful', { requestId: req.id });
    res.json({
      ok: 1,
      data: abdmResponse.data,
      timestamp: new Date().toISOString(),
    });

  } catch (error) {
    logger.error('ABHA validation failed', { 
      requestId: req.id, 
      error: error.message 
    });

    res.status(error.response?.status || 500).json({
      ok: 0,
      error: error.response?.data?.error || error.message,
      request_id: req.id,
    });
  }
});

/**
 * Consent Request Endpoint
 * POST /api/v3/consent/request
 * Initiates consent request in ABDM
 */
app.post('/api/v3/consent/request', authenticateToken, async (req, res) => {
  try {
    const { patient_abha, purpose, hi_types, date_range_from, date_range_to } = req.body;

    logger.info('Consent request initiated', { 
      requestId: req.id, 
      patient_abha: patient_abha ? patient_abha.substring(0, 5) + '***' : 'null' 
    });

    if (!patient_abha || !hi_types || !Array.isArray(hi_types)) {
      return res.status(400).json({ 
        ok: 0, 
        error: 'patient_abha, purpose, and hi_types array required' 
      });
    }

    const consentId = `CONS-${Date.now()}-${uuidv4().substring(0, 8)}`;

    // Call ABDM M3 API
    const abdmResponse = await axios.post(
      `${process.env.ABDM_M3_URL}/consent/request`,
      {
        consent_id: consentId,
        patient_abha: patient_abha,
        purpose: purpose || 'treatment',
        hi_types: hi_types,
        date_range_from: date_range_from,
        date_range_to: date_range_to,
        request_id: req.id,
      },
      {
        headers: {
          'Authorization': `Bearer ${process.env.ABDM_M3_TOKEN}`,
          'Content-Type': 'application/json',
          'X-Client-ID': process.env.BRIDGE_SOURCE_CODE,
        },
        timeout: parseInt(process.env.BRIDGE_SYNC_TIMEOUT || '30') * 1000,
      }
    );

    logger.info('Consent request successful', { requestId: req.id, consentId });
    res.json({
      ok: 1,
      consent_id: consentId,
      data: abdmResponse.data,
      timestamp: new Date().toISOString(),
    });

  } catch (error) {
    logger.error('Consent request failed', { 
      requestId: req.id, 
      error: error.message 
    });

    res.status(error.response?.status || 500).json({
      ok: 0,
      error: error.response?.data?.error || error.message,
      request_id: req.id,
    });
  }
});

/**
 * Bundle Push Endpoint
 * POST /api/v3/bundle/push
 * Pushes FHIR bundle to ABDM
 */
app.post('/api/v3/bundle/push', authenticateToken, async (req, res) => {
  try {
    const { fhir_bundle, consent_id, hi_type } = req.body;

    logger.info('Bundle push requested', { 
      requestId: req.id, 
      hi_type,
      consent_id: consent_id ? consent_id.substring(0, 10) + '***' : 'null'
    });

    if (!fhir_bundle || !consent_id || !hi_type) {
      return res.status(400).json({ 
        ok: 0, 
        error: 'fhir_bundle, consent_id, and hi_type required' 
      });
    }

    const bundleId = `BUN-${Date.now()}-${uuidv4().substring(0, 8)}`;

    // Call ABDM M3 API
    const abdmResponse = await axios.post(
      `${process.env.ABDM_M3_URL}/bundle/push`,
      {
        bundle_id: bundleId,
        hi_type: hi_type,
        consent_id: consent_id,
        fhir_bundle: fhir_bundle,
        request_id: req.id,
      },
      {
        headers: {
          'Authorization': `Bearer ${process.env.ABDM_M3_TOKEN}`,
          'Content-Type': 'application/json',
          'X-Client-ID': process.env.BRIDGE_SOURCE_CODE,
        },
        timeout: parseInt(process.env.BRIDGE_SYNC_TIMEOUT || '30') * 1000,
      }
    );

    logger.info('Bundle push successful', { requestId: req.id, bundleId });
    res.json({
      ok: 1,
      bundle_id: bundleId,
      data: abdmResponse.data,
      timestamp: new Date().toISOString(),
    });

  } catch (error) {
    logger.error('Bundle push failed', { 
      requestId: req.id, 
      error: error.message 
    });

    res.status(error.response?.status || 500).json({
      ok: 0,
      error: error.response?.data?.error || error.message,
      request_id: req.id,
    });
  }
});

/**
 * SNOMED CT Search Endpoint
 * GET /api/v3/snomed/search
 * Proxies to CSNOtk terminology service
 */
app.get('/api/v3/snomed/search', authenticateToken, async (req, res) => {
  try {
    const { term, return_limit = 10 } = req.query;

    logger.info('SNOMED search requested', { requestId: req.id, term });

    if (!term) {
      return res.status(400).json({ 
        ok: 0, 
        error: 'term parameter required' 
      });
    }

    const csnotkResponse = await axios.get(
      `${process.env.SNOMED_SERVICE_URL}/search/suggest`,
      {
        params: {
          term: term,
          returnlimit: return_limit,
        },
        timeout: parseInt(process.env.SNOMED_TIMEOUT || '10') * 1000,
      }
    );

    logger.info('SNOMED search successful', { requestId: req.id });
    res.json({
      ok: 1,
      data: csnotkResponse.data,
      timestamp: new Date().toISOString(),
    });

  } catch (error) {
    logger.error('SNOMED search failed', { 
      requestId: req.id, 
      error: error.message 
    });

    res.status(error.response?.status || 500).json({
      ok: 0,
      error: error.response?.data?.error || error.message,
      request_id: req.id,
    });
  }
});

/**
 * Bundle Status Endpoint
 * GET /api/v3/bundle/:bundleId/status
 * Checks status of previously pushed bundle
 */
app.get('/api/v3/bundle/:bundleId/status', authenticateToken, async (req, res) => {
  try {
    const { bundleId } = req.params;

    logger.info('Bundle status requested', { requestId: req.id, bundleId });

    const abdmResponse = await axios.get(
      `${process.env.ABDM_M3_URL}/bundle/${bundleId}/status`,
      {
        headers: {
          'Authorization': `Bearer ${process.env.ABDM_M3_TOKEN}`,
          'X-Client-ID': process.env.BRIDGE_SOURCE_CODE,
        },
        timeout: parseInt(process.env.BRIDGE_SYNC_TIMEOUT || '30') * 1000,
      }
    );

    logger.info('Bundle status retrieved', { requestId: req.id });
    res.json({
      ok: 1,
      data: abdmResponse.data,
      timestamp: new Date().toISOString(),
    });

  } catch (error) {
    logger.error('Bundle status check failed', { 
      requestId: req.id, 
      error: error.message 
    });

    res.status(error.response?.status || 500).json({
      ok: 0,
      error: error.response?.data?.error || error.message,
      request_id: req.id,
    });
  }
});

/**
 * Gateway Status Endpoint
 * GET /api/v3/gateway/status
 * Returns gateway service and dependency health
 */
app.get('/api/v3/gateway/status', authenticateToken, async (req, res) => {
  try {
    const status = {
      gateway: 'ok',
      abdm_m3: 'checking',
      snomed_service: 'checking',
      timestamp: new Date().toISOString(),
    };

    // Check ABDM M3 connectivity
    try {
      await axios.get(
        `${process.env.ABDM_M3_URL}/health`,
        { timeout: 5000 }
      );
      status.abdm_m3 = 'ok';
    } catch (e) {
      logger.warn('ABDM M3 health check failed', { error: e.message });
      status.abdm_m3 = 'unreachable';
    }

    // Check SNOMED service connectivity
    try {
      await axios.get(
        `${process.env.SNOMED_SERVICE_URL}/health`,
        { timeout: 5000 }
      );
      status.snomed_service = 'ok';
    } catch (e) {
      logger.warn('SNOMED service health check failed', { error: e.message });
      status.snomed_service = 'unreachable';
    }

    logger.info('Gateway status checked', { requestId: req.id });
    res.json({
      ok: status.abdm_m3 === 'ok' && status.snomed_service === 'ok' ? 1 : 0,
      data: status,
    });

  } catch (error) {
    logger.error('Gateway status check failed', { 
      requestId: req.id, 
      error: error.message 
    });

    res.status(500).json({
      ok: 0,
      error: error.message,
      request_id: req.id,
    });
  }
});

// ============================================
// Error Handlers
// ============================================

// 404 Handler
app.use((req, res) => {
  logger.warn('Not found', { path: req.path, method: req.method });
  res.status(404).json({
    ok: 0,
    error: 'Endpoint not found',
  });
});

// Global Error Handler
app.use((err, req, res, next) => {
  logger.error('Unhandled error', { 
    requestId: req.id, 
    error: err.message, 
    stack: err.stack 
  });

  res.status(500).json({
    ok: 0,
    error: NODE_ENV === 'production' ? 'Internal server error' : err.message,
    request_id: req.id,
  });
});

// ============================================
// Server Startup
// ============================================

app.listen(PORT, () => {
  logger.info(`🚀 ABDM Gateway Server running on port ${PORT}`, { 
    environment: NODE_ENV,
    bridgeSourceCode: process.env.BRIDGE_SOURCE_CODE,
  });
});

// Graceful Shutdown
process.on('SIGTERM', () => {
  logger.info('SIGTERM signal received: closing HTTP server');
  process.exit(0);
});

process.on('SIGINT', () => {
  logger.info('SIGINT signal received: closing HTTP server');
  process.exit(0);
});

export default app;
