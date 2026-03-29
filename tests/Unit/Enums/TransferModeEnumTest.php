<?php

use VoIPforAll\TFTPClient\Enums\TransferModeEnum;

test('has exactly three cases', function () {
    expect(TransferModeEnum::cases())->toHaveCount(3);
});

test('each case has the correct string value', function (TransferModeEnum $case, string $expected) {
    expect($case->value)->toBe($expected);
})->with([
    [TransferModeEnum::NETASCII, 'netascii'],
    [TransferModeEnum::OCTET, 'octet'],
    [TransferModeEnum::MAIL, 'mail'],
]);
