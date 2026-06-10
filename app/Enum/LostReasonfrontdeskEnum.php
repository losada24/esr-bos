<?php

namespace App\Enum;

enum LostReasonfrontdeskEnum: string
{
    case NO_RESPONSE_FROM_CLIENT = 'NO ANSWERS';
   // case CLIENT_NOT_INTERESTED = 'CLIENT NOT INTERESTED';
    //case BUDGET_ISSUES = 'BUDGET ISSUES';case DEALER = 'DEALER';
    case FAKE = 'FAKE';
    case DEALER = 'DEALER';
    case WORK = 'WORK';
    case STOCK = 'STOCK'; 
    case OTHER_REASONS = 'OTHER REASONS';



}
