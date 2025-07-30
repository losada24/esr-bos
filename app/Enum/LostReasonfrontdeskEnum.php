<?php

namespace App\Enum;

enum LostReasonfrontdeskEnum: string
{
    case NO_RESPONSE_FROM_CLIENT = 'NO RESPONSE FROM CLIENT';
    case CLIENT_NOT_INTERESTED = 'CLIENT NOT INTERESTED';
    case BUDGET_ISSUES = 'BUDGET ISSUES';
    case OTHER_REASONS = 'OTHER REASONS';

}
