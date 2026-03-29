<?php

use VoIPforAll\TFTPClient\Enums\ByteLimitEnum;

test('DATA has value 512', function () {
    expect(ByteLimitEnum::DATA->value)->toBe(512);
});

test('PACKET has value 516', function () {
    expect(ByteLimitEnum::PACKET->value)->toBe(516);
});

test('has exactly two cases', function () {
    expect(ByteLimitEnum::cases())->toHaveCount(2);
});
