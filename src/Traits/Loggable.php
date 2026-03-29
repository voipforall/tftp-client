<?php

namespace VoIPforAll\TFTPClient\Traits;

use Illuminate\Support\Facades\Log;
use VoIPforAll\TFTPClient\Enums\LogLevelEnum;
use VoIPforAll\TFTPClient\Exceptions\UnknowLogLevelException;

trait Loggable
{
    /**
     * @throws UnknowLogLevelException
     */
    public function logger(string $level, string $message, array $context = []): void
    {
        if (! config('tftp-client.logging')) {
            return;
        }

        $validLevel = LogLevelEnum::tryFrom($level);

        if ($validLevel === null) {
            throw new UnknowLogLevelException("Unknown log level: {$level}");
        }

        $context['connection'] = config('tftp-client.connection');

        Log::channel(config('tftp-client.logger_channel'))->log($level, $message, $context);
    }
}
