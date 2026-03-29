<?php

namespace VoIPforAll\TFTPClient\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use VoIPforAll\TFTPClient\TFTPClientServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            TFTPClientServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('tftp-client.connection', 'default');
        $app['config']->set('tftp-client.connections.default', [
            'host' => '127.0.0.1',
            'port' => 69,
            'transfer_mode' => 'octet',
        ]);
        $app['config']->set('tftp-client.logging', false);
        $app['config']->set('tftp-client.logger_channel', 'stack');
    }
}
