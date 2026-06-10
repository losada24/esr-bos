<?php

namespace App\Enum;

enum StatusUserEnum: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case BANNED = 'BANNED';
}
