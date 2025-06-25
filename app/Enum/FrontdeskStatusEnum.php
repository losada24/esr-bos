<?php

namespace App\Enum;

enum FrontdeskStatusEnum: string
{
    case NEW_CUSTOMER_REQUEST = 'New Customer Request';
    case NEW_REQUEST_FOLLOWUP = 'New Request Followup';
    case NEW_REQUEST_STANDBY = 'New Request Standby';
}
