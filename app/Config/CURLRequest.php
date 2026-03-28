<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class CURLRequest extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * CURLRequest Share Connection Options
     * --------------------------------------------------------------------------
     *
     * Share connection options between requests.
     *
     * @var list<int>
     *
     * @see https://www.php.net/manual/en/curl.constants.php#constant.curl-lock-data-connect
     */
    public array $shareConnectionOptions = [];

    /**
     * --------------------------------------------------------------------------
     * CURLRequest Share Options
     * --------------------------------------------------------------------------
     *
     * Whether share options between requests or not.
     *
     * If true, all the options won't be reset between requests.
     * It may cause an error request with unnecessary headers.
     */
    public bool $shareOptions = false;

    public function __construct()
    {
        parent::__construct();

        // Some cURL builds do not expose these constants; skip sharing in that case.
        if (defined('CURL_LOCK_DATA_CONNECT')) {
            $this->shareConnectionOptions[] = constant('CURL_LOCK_DATA_CONNECT');
        }

        if (defined('CURL_LOCK_DATA_DNS')) {
            $this->shareConnectionOptions[] = constant('CURL_LOCK_DATA_DNS');
        }
    }
}
