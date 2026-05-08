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
