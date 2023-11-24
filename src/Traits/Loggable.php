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
    public function logger($level, $message, $context = []): void
    {
        if (config('tftp-client.logging')) {
            $logChannel = config('tftp-client.logger_channel');
            $context['connection'] = config('tftp-client.connection');
            match ($level) {
                LogLevelEnum::EMERGENCY->value => Log::channel($logChannel)->emergency($message, $context),
                LogLevelEnum::ALERT->value => Log::channel($logChannel)->alert($message, $context),
                LogLevelEnum::CRITICAL->value => Log::channel($logChannel)->critical($message, $context),
                LogLevelEnum::ERROR->value => Log::channel($logChannel)->error($message, $context),
                LogLevelEnum::WARNING->value => Log::channel($logChannel)->warning($message, $context),
                LogLevelEnum::NOTICE->value => Log::channel($logChannel)->notice($message, $context),
                LogLevelEnum::INFO->value => Log::channel($logChannel)->info($message, $context),
                LogLevelEnum::DEBUG->value => Log::channel($logChannel)->debug($message, $context),
                default => throw new UnknowLogLevelException('Unknow Log Level'),
            };
        }
    }
}
