<?php

namespace VoIPforAll\TFTPClient\Enums;

enum TransferModeEnum: string
{
    case NETASCII = 'netascii';
    case OCTET = 'octet';
    case MAIL = 'mail';
}
