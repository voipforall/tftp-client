<?php

use VoIPforAll\TFTPClient\TFTPClient;
use VoIPforAll\TFTPClient\SocketMockState;

beforeEach(fn () => SocketMockState::enable());
afterEach(fn () => SocketMockState::disable());

test('merges default config', function () {
    expect(config('tftp-client.connection'))->toBe('default')
        ->and(config('tftp-client.connections'))->toBeArray()
        ->and(config('tftp-client.connections.default'))->toHaveKeys(['host', 'port', 'transfer_mode']);
});

test('registers tftp-client singleton', function () {
    $client = app('tftp-client');

    expect($client)->toBeInstanceOf(TFTPClient::class);
});

test('singleton returns same instance', function () {
    $client1 = app('tftp-client');
    $client2 = app('tftp-client');

    expect($client1)->toBe($client2);
});
