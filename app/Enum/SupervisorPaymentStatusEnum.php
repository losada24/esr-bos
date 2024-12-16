<?php

namespace App\Enum;

use Faker\Core\Number;

enum SupervisorPaymentStatusEnum: string
{
    case OPEN = 'OPEN' ;
    case PENDING = 'PENDING' ;
    case NO_PAID = 'NO PAID' ;
    case CLOSED = 'CLOSED' ;
}
