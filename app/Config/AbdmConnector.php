<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * ABDM Connector Configuration
 *
 * Controls which adapter the AbdmConnectorFactory instantiates.
 *
 * Override via .env:
 *   abdm.connector            = dreamsoft   # or direct_abdm
 *   abdm.directClientId       = <M3 client id>
 *   abdm.directClientSecret   = <M3 client secret>
 *   abdm.directBaseUrl        = https://dev.abdm.gov.in/api/v3
 *   abdm.directTimeoutSec     = 30
 */
class AbdmConnector extends BaseConfig
{
    /**
     * Which adapter to use.
     * 'dreamsoft'     -> DreamsoftConnector    (async queue via Dreamsoft bridge)
     * 'eatria_bridge' -> EAtriaBridgeConnector (sync HTTP to abdm-bridge.e-atria.in — recommended)
     * 'direct_abdm'   -> DirectAbdmConnector   (calls NHA ABDM APIs directly — future)
     */
    public string $connector = 'eatria_bridge';

    // ------------------------------------------------------------------
    // DreamsoftConnector settings
    // These are used by BridgeSyncService when routing through the
    // Dreamsoft bridge middleware.
    //
    // Priority order for each setting (highest wins):
    //   1. Environment variable  (.env or server env)
    //   2. PHP constant          (define() in a bootstrap file)
    //   3. This config file      (edit values below)
    //   4. hospital_setting DB   (runtime admin panel)
    //
    // Override via .env:
    //   BRIDGE_SYNC_URL         = https://your-dreamsoft-server.com/api/bridge
    //   BRIDGE_SYNC_TOKEN       = your-bearer-token
    //   BRIDGE_SOURCE_CODE      = HOSP001
    //   BRIDGE_SYNC_PROVIDER    = bridge
    // ------------------------------------------------------------------

    /**
     * Dreamsoft bridge server URL.
     * Example: https://bridge.dreamsofttech.in/api/v1/bridge
     * Set in .env: BRIDGE_SYNC_URL = https://...
     */
    public string $dreamsoftBridgeUrl = 'https://csnotk.e-atria.in/api/bridge';

    /**
     * Bearer token for Dreamsoft bridge authentication.
     * Set in .env: BRIDGE_SYNC_TOKEN = your-token
     * Do NOT commit real tokens to source control.
     */
    public string $dreamsoftBridgeToken = '';

    /**
     * Hospital/source code sent to the Dreamsoft bridge with every event.
     * Identifies this HMS instance on the bridge server.
     * Set in .env: BRIDGE_SOURCE_CODE = HOSP001
     */
    public string $dreamsoftSourceCode = '';

    /**
     * HTTP timeout in seconds for Dreamsoft bridge calls.
     * Set in .env: BRIDGE_SYNC_TIMEOUT = 20
     */
    public int $dreamsoftTimeoutSec = 20;

    /**
     * ABDM bridge server URL for ABDM/NHCX events.
     * Set in .env: ABDM_BRIDGE_URL = https://...
     */
    public string $abdmBridgeUrl = 'https://abdm-bridge.e-atria.in/api/v1/bridge';

    /**
     * Bearer token for ABDM bridge authentication.
     * Set in .env: ABDM_BRIDGE_TOKEN = your-token
     */
    public string $abdmBridgeToken = '';

    // ------------------------------------------------------------------
    // EAtriaBridgeConnector settings
    // Used when $connector = 'eatria_bridge'.
    //
    // Override via .env:
    //   EATRIA_BRIDGE_URL     = https://abdm-bridge.e-atria.in/api
    //   EATRIA_BRIDGE_TOKEN   = <api-key from gateway admin panel>
    //   EATRIA_BRIDGE_TIMEOUT = 30
    // ------------------------------------------------------------------

    /**
     * e-Atria ABDM gateway base URL (without trailing slash).
     * Set in .env: EATRIA_BRIDGE_URL = https://abdm-bridge.e-atria.in/api
     */
    public string $eatriaBridgeUrl = 'https://abdm-bridge.e-atria.in/api';

    /**
     * Bearer API key issued by the gateway admin panel for this HMS hospital.
     * Copy the API Key shown in HMS API Configuration → API Key.
     * Set in .env: EATRIA_BRIDGE_TOKEN = <your-api-key>
     * Do NOT commit real tokens to source control.
     */
    public string $eatriaBridgeToken = '';

    /**
     * HTTP timeout in seconds for e-Atria gateway calls.
     * Set in .env: EATRIA_BRIDGE_TIMEOUT = 30
     */
    public int $eatriaBridgeTimeoutSec = 30;

    // ------------------------------------------------------------------
    // DirectAbdmConnector settings
    // Unused when $connector = 'dreamsoft' or 'eatria_bridge'.
    // ------------------------------------------------------------------

    /** ABDM M3 base URL. Sandbox: https://dev.abdm.gov.in/api/v3 */
    public string $directAbdmBaseUrl = 'https://dev.abdm.gov.in/api/v3';

    /**
     * ABDM M3 client ID.
     * Set in .env: abdm.directClientId = YOUR_CLIENT_ID
     */
    public string $directAbdmClientId = '';

    /**
     * ABDM M3 client secret.
     * Set in .env: abdm.directClientSecret = YOUR_CLIENT_SECRET
     * Do NOT commit real credentials to source control.
     */
    public string $directAbdmClientSecret = '';

    /** HTTP timeout in seconds for direct ABDM API calls. */
    public int $directAbdmTimeoutSec = 30;

    // ------------------------------------------------------------------
    // FHIR record encryption key (AES-256-GCM)
    // Set in .env: ABDM_FHIR_ENCRYPTION_KEY = <64-char hex string>
    // Generate: php -r "echo bin2hex(random_bytes(32));"
    // REQUIRED on production; falls back to a derived key in development.
    // ------------------------------------------------------------------
    // ABDM_FHIR_ENCRYPTION_KEY =
}
