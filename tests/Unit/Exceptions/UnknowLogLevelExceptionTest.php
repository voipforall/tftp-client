<?php

use VoIPforAll\TFTPClient\Exceptions\UnknowLogLevelException;

test('extends Exception', function () {
    expect(new UnknowLogLevelException())->toBeInstanceOf(Exception::class);
});

test('accepts message', function () {
    $exception = new UnknowLogLevelException('Unknown log level: foo');

    expect($exception->getMessage())->toBe('Unknown log level: foo');
});
