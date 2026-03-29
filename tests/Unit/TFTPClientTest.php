<?php

use VoIPforAll\TFTPClient\Enums\OpcodeEnum;
use VoIPforAll\TFTPClient\SocketMockState;
use VoIPforAll\TFTPClient\TFTPClient;

beforeEach(fn () => SocketMockState::enable());
afterEach(fn () => SocketMockState::disable());

// --- Constructor ---

test('creates client with valid config', function () {
    $client = new TFTPClient();

    expect($client)->toBeInstanceOf(TFTPClient::class);
});

test('throws RuntimeException when config is missing', function () {
    config()->set('tftp-client.connection', 'nonexistent');

    new TFTPClient();
})->throws(RuntimeException::class, 'TFTP connection configuration is missing or invalid.');

// --- GET: single block ---

test('get returns file content for single block response', function () {
    $content = 'Hello TFTP';
    $dataPacket = pack('n', OpcodeEnum::DATA->value) . pack('n', 1) . $content;
    SocketMockState::queueRecvResponse($dataPacket);

    $client = new TFTPClient();
    $result = $client->get('test.txt');

    expect($result)->toBe($content);
});

// --- GET: multi-block ---

test('get concatenates multi-block responses', function () {
    $block1Content = str_repeat('A', 512);
    $block2Content = 'remaining data';

    $packet1 = pack('n', OpcodeEnum::DATA->value) . pack('n', 1) . $block1Content; // 516 bytes
    $packet2 = pack('n', OpcodeEnum::DATA->value) . pack('n', 2) . $block2Content;

    SocketMockState::queueRecvResponse($packet1);
    SocketMockState::queueRecvResponse($packet2);

    $client = new TFTPClient();
    $result = $client->get('large.txt');

    expect($result)->toBe($block1Content . $block2Content);
});

// --- GET: sends correct RRQ packet ---

test('get sends read request packet with correct opcode', function () {
    $content = 'data';
    $dataPacket = pack('n', OpcodeEnum::DATA->value) . pack('n', 1) . $content;
    SocketMockState::queueRecvResponse($dataPacket);

    $client = new TFTPClient();
    $client->get('/path/to/file.txt');

    $requestPacket = SocketMockState::$sentPackets[0]['data'];
    $opcode = unpack('n', substr($requestPacket, 0, 2))[1];
    $filenameEnd = strpos($requestPacket, chr(0), 2);
    $filename = substr($requestPacket, 2, $filenameEnd - 2);

    expect($opcode)->toBe(OpcodeEnum::READ->value)
        ->and($filename)->toBe('file.txt');
});

// --- GET: sends ACK after each block ---

test('get sends ack packets for each received block', function () {
    $block1 = pack('n', OpcodeEnum::DATA->value) . pack('n', 1) . str_repeat('X', 512);
    $block2 = pack('n', OpcodeEnum::DATA->value) . pack('n', 2) . 'end';

    SocketMockState::queueRecvResponse($block1);
    SocketMockState::queueRecvResponse($block2);

    $client = new TFTPClient();
    $client->get('file.txt');

    // Packet 0 = RRQ, Packet 1 = ACK block 1, Packet 2 = ACK block 2
    $ack1 = SocketMockState::$sentPackets[1]['data'];
    $ack2 = SocketMockState::$sentPackets[2]['data'];

    $ack1Opcode = unpack('n', substr($ack1, 0, 2))[1];
    $ack2Opcode = unpack('n', substr($ack2, 0, 2))[1];

    expect($ack1Opcode)->toBe(OpcodeEnum::ACK->value)
        ->and($ack2Opcode)->toBe(OpcodeEnum::ACK->value);
});

// --- GET: server error ---

test('get returns false on server error', function () {
    $errorPacket = pack('n', OpcodeEnum::ERROR->value) . pack('n', 1) . 'File not found' . chr(0);
    SocketMockState::queueRecvResponse($errorPacket);

    $client = new TFTPClient();
    $result = $client->get('missing.txt');

    expect($result)->toBeFalse();
});

// --- GET: unknown opcode ---

test('get returns false on unknown opcode', function () {
    $unknownPacket = pack('n', 99) . 'garbage';
    SocketMockState::queueRecvResponse($unknownPacket);

    $client = new TFTPClient();
    $result = $client->get('file.txt');

    expect($result)->toBeFalse();
});

// --- GET: socket timeout ---

test('get returns false on socket timeout', function () {
    SocketMockState::$recvFails = true;

    $client = new TFTPClient();
    $result = $client->get('file.txt');

    expect($result)->toBeFalse();
});

// --- PUT: single block success ---

test('put returns true on successful upload', function () {
    $content = 'file content';
    SocketMockState::setFileContents($content);

    // ACK for WRQ
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 0));
    // ACK for DATA block 1
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 1));

    $client = new TFTPClient();
    $result = $client->put('test.txt');

    expect($result)->toBeTrue();
});

// --- PUT: sends correct WRQ packet ---

test('put sends write request packet with correct opcode', function () {
    SocketMockState::setFileContents('data');
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 0));
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 1));

    $client = new TFTPClient();
    $client->put('/some/path/upload.bin');

    $requestPacket = SocketMockState::$sentPackets[0]['data'];
    $opcode = unpack('n', substr($requestPacket, 0, 2))[1];
    $filenameEnd = strpos($requestPacket, chr(0), 2);
    $filename = substr($requestPacket, 2, $filenameEnd - 2);

    expect($opcode)->toBe(OpcodeEnum::WRITE->value)
        ->and($filename)->toBe('upload.bin');
});

// --- PUT: sends DATA packet with pack('n') encoding ---

test('put sends data packet with correct block encoding', function () {
    $content = 'test data';
    SocketMockState::setFileContents($content);
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 0));
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 1));

    $client = new TFTPClient();
    $client->put('file.txt');

    // Packet 0 = WRQ, Packet 1 = DATA block 1
    $dataPacket = SocketMockState::$sentPackets[1]['data'];
    $opcode = unpack('n', substr($dataPacket, 0, 2))[1];
    $block = unpack('n', substr($dataPacket, 2, 2))[1];
    $payload = substr($dataPacket, 4);

    expect($opcode)->toBe(OpcodeEnum::DATA->value)
        ->and($block)->toBe(1)
        ->and($payload)->toBe($content);
});

// --- PUT: multi-block ---

test('put splits large content into 512-byte blocks', function () {
    $content = str_repeat('B', 1024) . 'extra';
    SocketMockState::setFileContents($content);

    // ACK for WRQ
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 0));
    // ACK for block 1
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 1));
    // ACK for block 2
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 2));
    // ACK for block 3
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 3));

    $client = new TFTPClient();
    $result = $client->put('large.bin');

    expect($result)->toBeTrue();

    // WRQ + 3 DATA blocks = 4 sent packets
    expect(SocketMockState::$sentPackets)->toHaveCount(4);

    // Verify block 1 has 512 bytes payload
    $block1Payload = substr(SocketMockState::$sentPackets[1]['data'], 4);
    expect(strlen($block1Payload))->toBe(512);

    // Verify block 2 has 512 bytes payload
    $block2Payload = substr(SocketMockState::$sentPackets[2]['data'], 4);
    expect(strlen($block2Payload))->toBe(512);

    // Verify block 3 has remaining 5 bytes
    $block3Payload = substr(SocketMockState::$sentPackets[3]['data'], 4);
    expect($block3Payload)->toBe('extra');
});

// --- PUT: empty file ---

test('put sends single empty data block for empty file', function () {
    SocketMockState::setFileContents('');
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 0));
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 1));

    $client = new TFTPClient();
    $result = $client->put('empty.txt');

    expect($result)->toBeTrue();

    // WRQ + 1 empty DATA block = 2 sent packets
    expect(SocketMockState::$sentPackets)->toHaveCount(2);

    $dataPayload = substr(SocketMockState::$sentPackets[1]['data'], 4);
    expect($dataPayload)->toBe('');
});

// --- PUT: file not readable ---

test('put returns false when file is not readable', function () {
    SocketMockState::setFileContents(false);

    $client = new TFTPClient();
    $result = $client->put('nonexistent.txt');

    expect($result)->toBeFalse();
});

// --- PUT: server error ---

test('put returns false on server error', function () {
    SocketMockState::setFileContents('data');
    $errorPacket = pack('n', OpcodeEnum::ERROR->value) . pack('n', 2) . 'Access violation' . chr(0);
    SocketMockState::queueRecvResponse($errorPacket);

    $client = new TFTPClient();
    $result = $client->put('forbidden.txt');

    expect($result)->toBeFalse();
});

// --- PUT: socket timeout ---

test('put returns false on socket timeout', function () {
    SocketMockState::setFileContents('data');
    SocketMockState::$recvFails = true;

    $client = new TFTPClient();
    $result = $client->put('file.txt');

    expect($result)->toBeFalse();
});

// --- PUT: unknown opcode on ACK ---

test('put returns false on unknown opcode response', function () {
    SocketMockState::setFileContents('data');
    $unknownPacket = pack('n', 99) . 'garbage';
    SocketMockState::queueRecvResponse($unknownPacket);

    $client = new TFTPClient();
    $result = $client->put('file.txt');

    expect($result)->toBeFalse();
});

// --- Destructor ---

test('destructor closes the socket', function () {
    SocketMockState::$closeCalled = false;

    $client = new TFTPClient();
    unset($client);

    expect(SocketMockState::$closeCalled)->toBeTrue();
});

// --- Transfer mode in request ---

test('request packet includes configured transfer mode', function () {
    config()->set('tftp-client.connections.default.transfer_mode', 'netascii');

    $dataPacket = pack('n', OpcodeEnum::DATA->value) . pack('n', 1) . 'x';
    SocketMockState::queueRecvResponse($dataPacket);

    $client = new TFTPClient();
    $client->get('file.txt');

    $requestPacket = SocketMockState::$sentPackets[0]['data'];

    expect($requestPacket)->toContain('netascii');
});

// --- Port configuration ---

test('sends request to configured host and port', function () {
    config()->set('tftp-client.connections.default.host', '192.168.1.100');
    config()->set('tftp-client.connections.default.port', 6969);

    $dataPacket = pack('n', OpcodeEnum::DATA->value) . pack('n', 1) . 'x';
    SocketMockState::queueRecvResponse($dataPacket);

    $client = new TFTPClient();
    $client->get('file.txt');

    expect(SocketMockState::$sentPackets[0]['addr'])->toBe('192.168.1.100')
        ->and(SocketMockState::$sentPackets[0]['port'])->toBe(6969);
});

// --- Logging on success ---

test('get logs success when logging is enabled', function () {
    config()->set('tftp-client.logging', true);
    config()->set('tftp-client.logger_channel', 'stack');

    $content = 'logged content';
    $dataPacket = pack('n', OpcodeEnum::DATA->value) . pack('n', 1) . $content;
    SocketMockState::queueRecvResponse($dataPacket);

    $channel = Mockery::mock();
    $channel->shouldReceive('log')
        ->once()
        ->with('info', 'File downloaded successfully', Mockery::on(function (array $ctx) use ($content) {
            return $ctx['filesize'] === strlen($content) && isset($ctx['PID']);
        }));

    Illuminate\Support\Facades\Log::shouldReceive('channel')
        ->with('stack')
        ->andReturn($channel);

    $client = new TFTPClient();
    $client->get('file.txt');
});

test('put logs success when logging is enabled', function () {
    config()->set('tftp-client.logging', true);
    config()->set('tftp-client.logger_channel', 'stack');

    $content = 'upload data';
    SocketMockState::setFileContents($content);
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 0));
    SocketMockState::queueRecvResponse(pack('n', OpcodeEnum::ACK->value) . pack('n', 1));

    $channel = Mockery::mock();
    $channel->shouldReceive('log')
        ->once()
        ->with('info', 'File uploaded successfully', Mockery::on(function (array $ctx) use ($content) {
            return $ctx['filesize'] === strlen($content) && isset($ctx['PID']);
        }));

    Illuminate\Support\Facades\Log::shouldReceive('channel')
        ->with('stack')
        ->andReturn($channel);

    $client = new TFTPClient();
    $client->put('file.txt');
});

// --- Logging on error ---

test('get logs error when server returns error', function () {
    config()->set('tftp-client.logging', true);
    config()->set('tftp-client.logger_channel', 'stack');

    $errorPacket = pack('n', OpcodeEnum::ERROR->value) . pack('n', 1) . 'File not found' . chr(0);
    SocketMockState::queueRecvResponse($errorPacket);

    $channel = Mockery::mock();
    $channel->shouldReceive('log')
        ->once()
        ->with('error', 'TFTP Server Error', Mockery::type('array'));

    Illuminate\Support\Facades\Log::shouldReceive('channel')
        ->with('stack')
        ->andReturn($channel);

    $client = new TFTPClient();
    $client->get('missing.txt');
});

test('put logs error when server returns error', function () {
    config()->set('tftp-client.logging', true);
    config()->set('tftp-client.logger_channel', 'stack');

    SocketMockState::setFileContents('data');
    $errorPacket = pack('n', OpcodeEnum::ERROR->value) . pack('n', 2) . 'Access denied' . chr(0);
    SocketMockState::queueRecvResponse($errorPacket);

    $channel = Mockery::mock();
    $channel->shouldReceive('log')
        ->once()
        ->with('error', 'TFTP Server Error', Mockery::type('array'));

    Illuminate\Support\Facades\Log::shouldReceive('channel')
        ->with('stack')
        ->andReturn($channel);

    $client = new TFTPClient();
    $client->put('forbidden.txt');
});
