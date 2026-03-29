<?php

namespace VoIPforAll\TFTPClient;

/**
 * Controls the behavior of mocked socket and filesystem functions.
 * Enable with SocketMockState::enable() before creating a TFTPClient instance.
 */
class SocketMockState
{
    public static bool $enabled = false;

    /** @var list<string> Queued responses for socket_recvfrom */
    public static array $recvResponses = [];

    public static int $recvIndex = 0;

    /** @var list<array{data: string, addr: string, port: int}> */
    public static array $sentPackets = [];

    public static bool $recvFails = false;

    public static int $communicationPort = 12345;

    public static bool $closeCalled = false;

    /** @var string|false Override for file_get_contents */
    public static string|false $fileContents = false;

    public static bool $fileContentsOverride = false;

    public static function enable(): void
    {
        static::$enabled = true;
        static::$recvResponses = [];
        static::$recvIndex = 0;
        static::$sentPackets = [];
        static::$recvFails = false;
        static::$communicationPort = 12345;
        static::$closeCalled = false;
        static::$fileContents = false;
        static::$fileContentsOverride = false;
    }

    public static function disable(): void
    {
        static::$enabled = false;
    }

    public static function queueRecvResponse(string $response): void
    {
        static::$recvResponses[] = $response;
    }

    public static function setFileContents(string|false $contents): void
    {
        static::$fileContentsOverride = true;
        static::$fileContents = $contents;
    }
}

function socket_create(int $domain, int $type, int $protocol): \Socket|false
{
    if (SocketMockState::$enabled) {
        return \socket_create($domain, $type, $protocol);
    }

    return \socket_create($domain, $type, $protocol);
}

function socket_set_option(\Socket $socket, int $level, int $optname, mixed $optval): bool
{
    if (SocketMockState::$enabled) {
        return true;
    }

    return \socket_set_option($socket, $level, $optname, $optval);
}

function socket_sendto(\Socket $socket, string $data, int $length, int $flags, string $addr, int $port): int|false
{
    if (SocketMockState::$enabled) {
        SocketMockState::$sentPackets[] = [
            'data' => $data,
            'addr' => $addr,
            'port' => $port,
        ];

        return $length;
    }

    return \socket_sendto($socket, $data, $length, $flags, $addr, $port);
}

/**
 * @param  string  $data
 * @param  string  $addr
 * @param  int  $port
 */
function socket_recvfrom(\Socket $socket, ?string &$data, int $length, int $flags, ?string &$addr, ?int &$port): int|false
{
    if (SocketMockState::$enabled) {
        if (SocketMockState::$recvFails) {
            return false;
        }

        $response = SocketMockState::$recvResponses[SocketMockState::$recvIndex++] ?? '';
        $data = $response;
        $port = SocketMockState::$communicationPort;

        return strlen($response);
    }

    return \socket_recvfrom($socket, $data, $length, $flags, $addr, $port);
}

function socket_close(\Socket $socket): void
{
    SocketMockState::$closeCalled = true;
    \socket_close($socket);
}

function socket_last_error(?\Socket $socket = null): int
{
    if (SocketMockState::$enabled) {
        return 110;
    }

    return \socket_last_error($socket);
}

function socket_strerror(int $error): string
{
    if (SocketMockState::$enabled) {
        return 'Connection timed out';
    }

    return \socket_strerror($error);
}

function file_get_contents(string $filename): string|false
{
    if (SocketMockState::$enabled && SocketMockState::$fileContentsOverride) {
        return SocketMockState::$fileContents;
    }

    return \file_get_contents($filename);
}
