<?php

namespace VoIPforAll\TFTPClient\Enums;

enum ByteLimitEnum: int
{
    case DATA = 512;
    case PACKET = 516;
}