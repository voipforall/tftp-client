<?php

namespace VoIPforAll\TFTPClient;

use Exception;
use RuntimeException;
use Socket;
use VoIPforAll\TFTPClient\Enums\ByteLimitEnum;
use VoIPforAll\TFTPClient\Enums\LogLevelEnum;
use VoIPforAll\TFTPClient\Enums\OpcodeEnum;
use VoIPforAll\TFTPClient\Exceptions\ServerException;
use VoIPforAll\TFTPClient\Exceptions\UnknowOpcodeException;
use VoIPforAll\TFTPClient\Traits\Loggable;

class TFTPClient
{
    use Loggable;

    private const SOCKET_TIMEOUT_SECONDS = 5;

    private Socket $socket;

    private string $host;

    private int $port;

    private int $communicationPort;

    private string $transferMode;

    public function __construct()
    {
        $connectionData = config('tftp-client.connections.' . config('tftp-client.connection'));

        if (! is_array($connectionData)) {
            throw new RuntimeException('TFTP connection configuration is missing or invalid.');
        }

        $this->host = $connectionData['host'];
        $this->port = (int) $connectionData['port'];
        $this->transferMode = $connectionData['transfer_mode'];
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => self::SOCKET_TIMEOUT_SECONDS,
            'usec' => 0,
        ]);
    }

    public function __destruct()
    {
        socket_close($this->socket);
    }

    public function get(string $filename): bool|string
    {
        try {
            $this->sendRequestPacket(OpcodeEnum::READ, $filename);

            $data = '';
            do {
                $buffer = $this->receiveData();
                $this->sendAckPacket(substr($buffer, 2, 2));
                $data .= substr($buffer, 4);
            } while (strlen($buffer) === ByteLimitEnum::PACKET->value);

            $this->logger(LogLevelEnum::INFO->value, 'File downloaded successfully', [
                'PID' => getmypid(),
                'filename' => $filename,
                'filesize' => strlen($data),
            ]);

            return $data;
        } catch (ServerException $exception) {
            $this->logger(LogLevelEnum::ERROR->value, 'TFTP Server Error', [
                'PID' => getmypid(),
                'error' => $exception->getCode(),
                'message' => preg_replace('/[^\PC\s]/u', '', $exception->getMessage()),
            ]);

            return false;
        } catch (Exception $exception) {
            $this->logger(LogLevelEnum::ERROR->value, 'Unknown exception', [
                'PID' => getmypid(),
                'error' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function put(string $filename): bool
    {
        try {
            $content = file_get_contents($filename);

            if ($content === false) {
                throw new RuntimeException("Cannot read file: {$filename}");
            }

            $filesize = strlen($content);
            $this->sendRequestPacket(OpcodeEnum::WRITE, $filename);
            $this->receiveAck();
            $this->sendFileContent($content);

            $this->logger(LogLevelEnum::INFO->value, 'File uploaded successfully', [
                'PID' => getmypid(),
                'filename' => $filename,
                'filesize' => $filesize,
            ]);

            return true;
        } catch (ServerException $exception) {
            $this->logger(LogLevelEnum::ERROR->value, 'TFTP Server Error', [
                'PID' => getmypid(),
                'error' => $exception->getCode(),
                'message' => preg_replace('/[^\PC\s]/u', '', $exception->getMessage()),
            ]);

            return false;
        } catch (Exception $exception) {
            $this->logger(LogLevelEnum::ERROR->value, 'Unknown exception', [
                'PID' => getmypid(),
                'error' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendRequestPacket(OpcodeEnum $opcode, string $filename): void
    {
        $file = pathinfo($filename, PATHINFO_BASENAME);

        $packet = pack('n', $opcode->value) . $file . chr(0) . $this->transferMode . chr(0);
        socket_sendto($this->socket, $packet, strlen($packet), 0x100, $this->host, $this->port);
    }

    /**
     * Send file content split into 512-byte blocks per RFC 1350.
     * Each block must be acknowledged before sending the next.
     */
    private function sendFileContent(string $content): void
    {
        $blocks = str_split($content, ByteLimitEnum::DATA->value);

        // Handle empty file: send a single empty DATA block per RFC 1350
        if ($blocks === ['' => ''] || $blocks === false) {
            $blocks = [''];
        }

        $blockNumber = 1;
        foreach ($blocks as $block) {
            $this->sendDataPacket($blockNumber, $block);
            $this->receiveAck();
            $blockNumber++;
        }
    }

    /**
     * @throws ServerException
     * @throws UnknowOpcodeException
     */
    private function receiveData(): string
    {
        $buffer = '';
        $tempPort = 0;
        $result = socket_recvfrom(
            $this->socket,
            $buffer,
            ByteLimitEnum::PACKET->value,
            0,
            $this->host,
            $tempPort
        );

        if ($result === false) {
            throw new RuntimeException('Socket receive timed out or failed: ' . socket_strerror(socket_last_error($this->socket)));
        }

        $this->communicationPort = $tempPort;

        $returnedOpcode = ord($buffer[1]);
        return match ($returnedOpcode) {
            OpcodeEnum::DATA->value => $buffer,
            OpcodeEnum::ERROR->value => throw new ServerException($buffer, $returnedOpcode),
            default => throw new UnknowOpcodeException("Unexpected opcode: {$returnedOpcode}"),
        };
    }

    /**
     * @throws ServerException
     * @throws UnknowOpcodeException
     */
    private function receiveAck(): void
    {
        $buffer = '';
        $tempPort = 0;
        $result = socket_recvfrom(
            $this->socket,
            $buffer,
            ByteLimitEnum::PACKET->value,
            0,
            $this->host,
            $tempPort
        );

        if ($result === false) {
            throw new RuntimeException('Socket receive timed out or failed: ' . socket_strerror(socket_last_error($this->socket)));
        }

        $this->communicationPort = $tempPort;

        $returnedOpcode = ord($buffer[1]);
        match ($returnedOpcode) {
            OpcodeEnum::ACK->value => null,
            OpcodeEnum::ERROR->value => throw new ServerException($buffer, $returnedOpcode),
            default => throw new UnknowOpcodeException("Unexpected opcode: {$returnedOpcode}"),
        };
    }

    private function sendDataPacket(int $block, string $content): void
    {
        $packet = pack('n', OpcodeEnum::DATA->value) . pack('n', $block) . $content;
        socket_sendto($this->socket, $packet, strlen($packet), 0, $this->host, $this->communicationPort);
    }

    private function sendAckPacket(string $blockBytes): void
    {
        $packet = pack('n', OpcodeEnum::ACK->value) . $blockBytes;
        socket_sendto($this->socket, $packet, strlen($packet), 0, $this->host, $this->communicationPort);
    }
}
