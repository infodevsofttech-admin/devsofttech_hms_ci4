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
     * 'dreamsoft'   -> DreamsoftConnector  (current default — routes via Dreamsoft bridge)
     * 'direct_abdm' -> DirectAbdmConnector (future — calls NHA ABDM APIs directly)
     */
    public string $connector = 'dreamsoft';

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
    public string $dreamsoftBridgeUrl = '';

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

    // ------------------------------------------------------------------
    // DirectAbdmConnector settings
    // Unused when $connector = 'dreamsoft'.
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
}
