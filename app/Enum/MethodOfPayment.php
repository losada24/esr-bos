<?php

namespace App\Enum;

enum MethodOfPayment: string
{
    case CASH = 'CASH';
    case FINANCED = 'FINANCED';
    case FINANCEDCASH = 'CASH AND FINANCED';
    case AIA = 'AIA';
    case ZELLE = 'ZELLE';
    case CHECK = 'CHECK';
}
