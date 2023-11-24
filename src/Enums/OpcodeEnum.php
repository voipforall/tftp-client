<?php

namespace VoIPforAll\TFTPClient\Enums;

enum OpcodeEnum: int
{
    case READ = 1;
    case WRITE = 2;
    case DATA = 3;
    case ACK = 4;
    case ERROR = 5;
}
