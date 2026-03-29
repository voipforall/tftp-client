<?php

test('config has required top-level keys', function () {
    $config = config('tftp-client');

    expect($config)->toHaveKeys(['connection', 'connections', 'logging', 'logger_channel']);
});

test('default connection points to an existing connection entry', function () {
    $connection = config('tftp-client.connection');
    $connections = config('tftp-client.connections');

    expect($connections)->toHaveKey($connection);
});

test('each connection has host, port, and transfer_mode', function () {
    $connections = config('tftp-client.connections');

    foreach ($connections as $name => $conn) {
        expect($conn)->toHaveKeys(['host', 'port', 'transfer_mode'], "Connection '{$name}' is missing required keys");
    }
});

test('logging defaults to false', function () {
    expect(config('tftp-client.logging'))->toBeFalse();
});
