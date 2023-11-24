<?php

namespace VoIPforAll\TFTPClient;

use Exception;
use VoIPforAll\TFTPClient\Enums\ByteLimitEnum;
use VoIPforAll\TFTPClient\Enums\LogLevelEnum;
use VoIPforAll\TFTPClient\Enums\OpcodeEnum;
use VoIPforAll\TFTPClient\Exceptions\ServerException;
use VoIPforAll\TFTPClient\Exceptions\UnknowOpcodeException;
use VoIPforAll\TFTPClient\Traits\Loggable;

class TFTPClient
{
    use Loggable;

    private $socket;

    private string $host;

    private int $port;

    private int $communicationPort;

    private string $transferMode;

    public function __construct()
    {
        $connectionData = config('tftp-client.connections.' . config('tftp-client.connection'));
        $this->host = $connectionData['host'];
        $this->port = $connectionData['port'];
        $this->transferMode = $connectionData['transfer_mode'];
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    public function get(string $filename): bool|string
    {
        try {
            $this->sendReadPacket($filename);

            $data = '';
            do {
                $buffer = $this->getServerResponse();
                $this->sendAckPacket(substr($buffer, 2, 2));
                $data .= substr($buffer, 4);
            } while (strlen($buffer) === ByteLimitEnum::PACKET->value);

            $this->logger(LogLevelEnum::INFO->value, 'File downloaded successfully', [
                'PID' => getmypid(),
                'filename' => $filename,
                'filesize' => strlen($buffer),
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
            $this->sendWritePacket($filename);
            $filesize = filesize($filename);
            $this->processFile($filename);

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

    /**
     * @throws ServerException
     * @throws UnknowOpcodeException
     */
    private function processFile(string $filename): void
    {
        $this->getServerResponse();
        $this->sendDataPacket(1, file_get_contents($filename));
    }

    private function sendReadPacket(string $filename): void
    {
        $file = pathinfo($filename, PATHINFO_BASENAME);

        $packet = chr(0) . chr(OpcodeEnum::READ->value) . $file . chr(0) . $this->transferMode . chr(0);
        socket_sendto($this->socket, $packet, strlen($packet), 0x100, $this->host, $this->port);
    }

    private function sendWritePacket(string $filename): void
    {
        $file = pathinfo($filename, PATHINFO_BASENAME);

        $packet = chr(0) . chr(OpcodeEnum::WRITE->value) . $file . chr(0) . $this->transferMode . chr(0);
        socket_sendto($this->socket, $packet, strlen($packet), 0x100, $this->host, $this->port);
    }

    /**
     * @throws ServerException
     * @throws UnknowOpcodeException
     */
    private function getServerResponse(): string
    {
        $buffer = '';
        $tempPort = 0;
        socket_recvfrom(
            $this->socket,
            $buffer,
            ByteLimitEnum::PACKET->value,
            0,
            $this->host,
            $tempPort
        );

        $this->communicationPort = $tempPort;

        $returnedOpcode = ord($buffer[1]);
        return match ($returnedOpcode) {
            OpcodeEnum::DATA->value => $buffer,
            OpcodeEnum::ACK->value => true,
            OpcodeEnum::ERROR->value => throw new ServerException($buffer, $returnedOpcode),
            default => throw new UnknowOpcodeException('OPCODE Unknow'),
        };
    }

    private function sendDataPacket(int $block, bool|string $content): void
    {
        $packet = chr(0) . chr(OpcodeEnum::DATA->value) . chr(0) . chr($block) . $content;
        socket_sendto($this->socket, $packet, strlen($packet), 0, $this->host, $this->communicationPort);
    }

    private function sendAckPacket(string $buffer): void
    {
        $packet = chr(0) . chr(OpcodeEnum::ACK->value) . $buffer;
        socket_sendto($this->socket, $packet, strlen($packet), 0, $this->host, $this->communicationPort);
    }
}
