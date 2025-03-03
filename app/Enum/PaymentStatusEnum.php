<?php

namespace App\Enum;

use Faker\Core\Number;

enum PaymentStatusEnum: string
{
    case REVIEW = 'REVIEW' ;
    case PAID = 'PAID' ;
}
