<?php

namespace App\Enum;

enum ServiceControlClosureResultEnum: string
{
    case COMPLETED = 'COMPLETED';
    case COMPLETED_WITH_PENDING = 'COMPLETED WITH PENDING';
    case REQUIRES_REWORK = 'REQUIRES REWORK';
}
