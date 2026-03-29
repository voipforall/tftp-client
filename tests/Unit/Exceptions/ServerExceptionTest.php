<?php

use VoIPforAll\TFTPClient\Exceptions\ServerException;

test('extends Exception', function () {
    expect(new ServerException())->toBeInstanceOf(Exception::class);
});

test('accepts message and code', function () {
    $exception = new ServerException('Server error', 5);

    expect($exception->getMessage())->toBe('Server error')
        ->and($exception->getCode())->toBe(5);
});
