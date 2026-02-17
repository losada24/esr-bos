<?php

namespace App\Enum;

enum PaymentInstallmentStatusEnum: string
{
    case PENDING = 'PENDING';
    case PARTIAL = 'PARTIAL';
    case PAID = 'PAID';
    case OVERPAID = 'OVERPAID';
}
