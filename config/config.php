<?php

use VoIPforAll\TFTPClient\Enums\TransferModeEnum;

return [

    /*
    |--------------------------------------------------------------------------
    | Default TFTP Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the TFTP connections below you wish
    | to use as your default connection for all TFTP operations. Of
    | course you may add as many connections you'd like below.
    |
    */

    'connection' => env('TFTP_CLIENT_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | TFTP Connections
    |--------------------------------------------------------------------------
    |
    | Below you may configure each TFTP connection your application requires
    | access to. Be sure to include a valid base HOST and TRANSFER MODE,
    | otherwise you may not be able to connect in the server.
    |
    */

    'connections' => [

        'default' => [
            'host' => env('TFTP_HOST', '127.0.0.1'),
            'port' => env('TFTP_PORT', 69),
            'transfer_mode' => env('TFTP_TRANSFER_MODE', TransferModeEnum::OCTET->value),
        ],

        'custom' => [
            'host' => env('TFTP_CUSTOM_HOST', '127.0.0.1'),
            'port' => env('TFTP_CUSTOM_PORT', 69),
            'transfer_mode' => env('TFTP_CUSTOM_TRANSFER_MODE', TransferModeEnum::OCTET->value),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | TFTP Logging
    |--------------------------------------------------------------------------
    |
    | When TFTP_LOGGING is enabled, all connections and transfers
    | operations are logged using the application logging
    | driver selected in TFTP_LOGGER_CHANNEL. This
    | can assist in debugging issues and more.
    |
    */

    'logging' => env('TFTP_LOGGING', false),

    'logger_channel' => env('TFTP_LOGGER_CHANNEL', env('LOG_CHANNEL')),
];