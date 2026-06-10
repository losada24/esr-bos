<?php

namespace App\Enum;

enum CommissionBeneficiaryRelationEnum: string
{
    case OWNER = 'OWNER';
    case EMPLOYEE = 'EMPLOYEE';
    case REMEASURER = 'REMEASURER';
    case REFERRAL = 'REFERRAL';
    case EXTERNAL = 'EXTERNAL';
}
