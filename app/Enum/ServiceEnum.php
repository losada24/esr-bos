<?php

namespace App\Enum;

enum ServiceEnum: string
{
    case DELIVERY = 'DELIVERY ONLY';
    case INSTALLATION = 'DELIVERY AND INSTALLATION';
    case PICKUP = 'PICKUP';
    case INSTALLATION_ONLY = 'INSTALLATION';
}