<?php

namespace App\Enum;

enum CommissionPaymentStatusEnum: string
{
    case OPEN = 'OPEN';
    case REVIEW = 'REVIEW';
    case PAID = 'PAID';
    case CANCELED = 'CANCELED';
}
