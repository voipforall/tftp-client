<?php

use Illuminate\Support\Facades\Log;
use VoIPforAll\TFTPClient\Exceptions\UnknowLogLevelException;

beforeEach(function () {
    $this->loggable = new class
    {
        use \VoIPforAll\TFTPClient\Traits\Loggable;
    };
});

test('does nothing when logging is disabled', function () {
    config()->set('tftp-client.logging', false);

    Log::shouldReceive('channel')->never();

    $this->loggable->logger('info', 'test message');
});

test('throws UnknowLogLevelException for invalid level', function () {
    config()->set('tftp-client.logging', true);

    $this->loggable->logger('invalid_level', 'test message');
})->throws(UnknowLogLevelException::class, 'Unknown log level: invalid_level');

test('logs message with correct level when logging is enabled', function (string $level) {
    config()->set('tftp-client.logging', true);
    config()->set('tftp-client.logger_channel', 'stack');
    config()->set('tftp-client.connection', 'default');

    $channel = Mockery::mock();
    $channel->shouldReceive('log')
        ->once()
        ->with($level, 'test message', Mockery::on(function (array $context) {
            return $context['connection'] === 'default' && $context['key'] === 'value';
        }));

    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturn($channel);

    $this->loggable->logger($level, 'test message', ['key' => 'value']);
})->with([
    'emergency',
    'alert',
    'critical',
    'error',
    'warning',
    'notice',
    'info',
    'debug',
]);

test('appends connection name to context', function () {
    config()->set('tftp-client.logging', true);
    config()->set('tftp-client.logger_channel', 'daily');
    config()->set('tftp-client.connection', 'custom');

    $channel = Mockery::mock();
    $channel->shouldReceive('log')
        ->once()
        ->with('info', 'msg', Mockery::on(fn (array $ctx) => $ctx['connection'] === 'custom'));

    Log::shouldReceive('channel')->with('daily')->andReturn($channel);

    $this->loggable->logger('info', 'msg');
});
