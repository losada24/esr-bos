<?php

namespace App\Enum;

use Faker\Core\Number;

enum InstallerPaymentStatusEnum: string
{
    case OPEN = 'OPEN' ;
    case PENDING = 'PENDING' ;
    case PARTIALLY_PAID = 'PARTIALLY PAID';
    case FULLY_PAID = 'FULLY PAID';
    case NO_PAID = 'NO PAID' ;
    case CLOSED = 'CLOSED' ;
}
