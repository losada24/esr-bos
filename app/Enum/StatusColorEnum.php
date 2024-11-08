<?php

namespace App\Enum;

enum StatusColorEnum: string
{
    case PLANNED = '#9333ff';
    case PLANNED_INSTALLATION = '#5FE3FB';
    case PLANNED_INSTALLATION_EVENT = '#0a7bd1';
    case CONFIRMED = '#F4F443';
    case CONFIRMED_INSTALLATION = '#ffb533';
    case CONFIRMED_DELIVERY = '#FF8D33';
    case DELAY_PERMITS = '#AC0505';
}