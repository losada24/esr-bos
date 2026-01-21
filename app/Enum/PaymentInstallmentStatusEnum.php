<?php

namespace App\Enum;

enum PaymentInstallmentStatusEnum: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';
}
