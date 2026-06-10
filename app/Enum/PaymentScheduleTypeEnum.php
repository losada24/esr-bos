<?php

namespace App\Enum;

enum PaymentScheduleTypeEnum: string
{
    case ALL_SERVICES = 'All Services';
    case WITHOUT_PERMITS = 'Without Permits';
    case WITHOUT_PERMITS_INSTALLATION = 'Without Permits and installation';
    case PROJECT_MORE_THAN_50K_ALL_SERVICES = 'Project more than 50,000 USD All Services';
    case PROJECT_MORE_THAN_50K_WITHOUT_PERMIT = 'Project more than 50 000 USD Without permit';
    case FULL_PAYMENT = 'Full Payment';
    case CUSTOMIZED = 'CUSTOMIZED';
}
