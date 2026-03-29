<?php

use VoIPforAll\TFTPClient\Exceptions\UnknowOpcodeException;

test('extends Exception', function () {
    expect(new UnknowOpcodeException())->toBeInstanceOf(Exception::class);
});

test('accepts message', function () {
    $exception = new UnknowOpcodeException('Unexpected opcode: 99');

    expect($exception->getMessage())->toBe('Unexpected opcode: 99');
});
