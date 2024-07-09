<?php

namespace App\Enum;

enum MethodOfPayment: string
{
    case CASH = 'CASH';
    case FINANCED = 'FINANCED';
}
