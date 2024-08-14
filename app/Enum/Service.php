<?php

namespace App\Enum;

enum Service: string
{
    case DELIVERY = 'DELIVERY ONLY';
    case INSTALLATION = 'DELIVERY AND INSTALLATION';
    case PICKUP = 'PICKUP';
}