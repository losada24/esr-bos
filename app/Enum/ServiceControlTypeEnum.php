<?php

namespace App\Enum;

enum ServiceControlTypeEnum: string
{
    case ADJUSTMENT = 'ADJUSTMENT';
    case GLASS = 'GLASS';
    case HARDWARE = 'HARDWARE';
    case SEALING = 'SEALING';
    case OTHER = 'OTHER';
}
