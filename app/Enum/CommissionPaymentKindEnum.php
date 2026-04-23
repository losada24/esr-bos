<?php

namespace App\Enum;

enum CommissionPaymentKindEnum: string
{
    case REGULAR = 'REGULAR';
    case EXTRA_ADJUSTMENT = 'EXTRA_ADJUSTMENT';
}
