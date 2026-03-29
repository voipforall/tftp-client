<?php

use VoIPforAll\TFTPClient\Enums\OpcodeEnum;

test('has exactly five cases', function () {
    expect(OpcodeEnum::cases())->toHaveCount(5);
});

test('each case has the correct integer value', function (OpcodeEnum $case, int $expected) {
    expect($case->value)->toBe($expected);
})->with([
    [OpcodeEnum::READ, 1],
    [OpcodeEnum::WRITE, 2],
    [OpcodeEnum::DATA, 3],
    [OpcodeEnum::ACK, 4],
    [OpcodeEnum::ERROR, 5],
]);
