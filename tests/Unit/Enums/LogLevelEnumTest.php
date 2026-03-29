<?php

use VoIPforAll\TFTPClient\Enums\LogLevelEnum;

test('has all 8 PSR log levels', function () {
    expect(LogLevelEnum::cases())->toHaveCount(8);
});

test('each case has the correct string value', function (LogLevelEnum $case, string $expected) {
    expect($case->value)->toBe($expected);
})->with([
    [LogLevelEnum::EMERGENCY, 'emergency'],
    [LogLevelEnum::ALERT, 'alert'],
    [LogLevelEnum::CRITICAL, 'critical'],
    [LogLevelEnum::ERROR, 'error'],
    [LogLevelEnum::WARNING, 'warning'],
    [LogLevelEnum::NOTICE, 'notice'],
    [LogLevelEnum::INFO, 'info'],
    [LogLevelEnum::DEBUG, 'debug'],
]);

test('tryFrom returns case for valid value', function () {
    expect(LogLevelEnum::tryFrom('info'))->toBe(LogLevelEnum::INFO);
});

test('tryFrom returns null for invalid value', function () {
    expect(LogLevelEnum::tryFrom('invalid'))->toBeNull();
});
