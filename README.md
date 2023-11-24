<p align="center">
<img src="https://github.com/voipforall/tftp-client/assets/30990097/558b85e8-538d-4251-9055-513bd3fe2b87">
</p>

# TFTP Client for Laravel


[![Latest Version on Packagist](https://img.shields.io/packagist/v/voipforall/tftp-client.svg?style=flat-square)](https://packagist.org/packages/voipforall/tftp-client)
[![Total Downloads](https://img.shields.io/packagist/dt/voipforall/tftp-client.svg?style=flat-square)](https://packagist.org/packages/voipforall/tftp-client)
![GitHub Actions](https://github.com/voipforall/tftp-client/actions/workflows/main.yml/badge.svg)

A PHP TFTP Client compliant with the [RFC 1350](https://datatracker.ietf.org/doc/html/rfc1350) compatible with [Laravel Framework](https://www.laravel.com) 

## Installation

You can install the package via composer:

```bash
composer require voipforall/tftp-client
```

## Requirements
- PHP ^8.1 (with ext-sockets enabled)
- Laravel 10.x

## Quick start
Configure in your `.env` file the following entries 
```bash
TFTP_HOST=127.0.0.1
TFTP_PORT=69
```

### To upload files
```php
use VoIPforAll\TFTPClient\TFTPClient;

$client = new TFTPClient;
$response = $client->put('path/to/your/file-to-send.txt');

dd($response) -> true
```
the `put` method will return a boolean accordingly with the success of the operation.

### To download files
```php
use VoIPforAll\TFTPClient\TFTPClient;

$client = new TFTPClient;
$content = $client->get('file-to-download.txt');

dd($content) -> "Download Ok"
```
The `get` method will return `false` if something goes wrong, if the file exists and is readable will be returned its content. 

## Publish the config file

To publish the config file use the Artisan command `vendor:publish` to copy the config file to your project
```bash
php artisan vendor:publish --tag=config
```
or
```bash
php artisan vendor:publish --provider=VoIPforAll\\TFTPClient\\TFTPClientServiceProvider
```

## Multiples TFTP Connections

You can have multiple connections with different TFTP Servers, take a look in the `tftp-client.php` config file
```php
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
            'host' => env('TFTP_CUSTOM_HOST', 'tftp.myhost.com'),
            'port' => env('TFTP_CUSTOM_PORT', 69),
            'transfer_mode' => env('TFTP_CUSTOM_TRANSFER_MODE', TransferModeEnum::OCTET->value),
        ],

    ],
```
The available transfer modes are:
- netascii
- octet (default)
- mail

Check the [RFC 1350](https://datatracker.ietf.org/doc/html/rfc1350) to more details about the transfer mode

## Logging the operations
If you want to log all TFTP operations just set the `TFTP_LOGGING` to `true` in your `.env` file. You can also select the appropriate log channel using the `TFTP_LOGGER_CHANNEL`, the defalt log channel will be used if you leave this unset.
```php
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
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email cristiano@voipforall.com.br instead of using the issue tracker.

## Credits

-   [VoIPforAll](https://github.com/voipforall)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
