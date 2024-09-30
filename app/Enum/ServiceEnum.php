<?php

namespace App\Enum;

enum ServiceEnum: string
{
    case DELIVERY = 'DELIVERY ONLY';
    case INSTALLATION = 'DELIVERY AND INSTALLATION';
    case PICKUP = 'PICKUP';
}