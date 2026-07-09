<?php

namespace App\Enum;

enum ServiceControlRequestOriginEnum: string
{
    case OWNER = 'OWNER';
    case SERVICE = 'SERVICE';
}
