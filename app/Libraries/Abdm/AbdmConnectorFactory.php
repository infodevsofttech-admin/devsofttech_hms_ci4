<?php

namespace App\Libraries\Abdm;

/**
 * AbdmConnectorFactory
 *
 * Instantiates the configured ABDM connector adapter.
 *
 * Usage:
 *   $connector = AbdmConnectorFactory::make();
 *   $result    = $connector->validateAbha('12345678901234');
 *
 * Configure via Config\AbdmConnector::$connector or .env:
 *   abdm.connector = dreamsoft        # default — Dreamsoft bridge middleware
 *   abdm.connector = direct_abdm      # future — direct NHA/ABDM API calls
 */
class AbdmConnectorFactory
{
    /**
     * Build and return the configured connector adapter.
     *
     * @throws \InvalidArgumentException if the connector name is unknown
     */
    public static function make(): AbdmConnectorInterface
    {
        $config    = config('AbdmConnector');
        $connector = strtolower(trim((string) ($config->connector ?? 'dreamsoft')));

        switch ($connector) {
            case 'dreamsoft':
                return new DreamsoftConnector();

            case 'direct_abdm':
            case 'direct':
                return new DirectAbdmConnector();

            default:
                throw new \InvalidArgumentException(
                    "AbdmConnectorFactory: unknown connector '{$connector}'. "
                    . "Valid values: 'dreamsoft', 'direct_abdm'."
                );
        }
    }
}
