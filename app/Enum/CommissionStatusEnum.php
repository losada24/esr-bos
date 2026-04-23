<?php

namespace App\Enum;

enum CommissionStatusEnum: string
{
    case OPEN = 'OPEN';
    case PARTIALLY_PAID = 'PARTIALLY_PAID';
    case FULLY_PAID = 'FULLY_PAID';
    case CANCELED = 'CANCELED';
}
