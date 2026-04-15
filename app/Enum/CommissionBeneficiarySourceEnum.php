<?php

namespace App\Enum;

enum CommissionBeneficiarySourceEnum: string
{
    case USER = 'USER';
    case REFERRAL = 'REFERRAL';
    case EXTERNAL = 'EXTERNAL';
}
