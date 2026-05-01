<?php

namespace App\Enum;

enum ServiceControlStatusEnum: string
{
    case PENDING = 'PENDING';
    case IN_PROGRESS = 'IN PROGRESS';
    case WAITING_FOR_PART = 'WAITING FOR PART';
    case PART_RECIEVED = 'Part Recieved';
    case READY_TO_SCHEDULE = 'READY TO SCHEDULE';
    case SCHEDULED = 'SCHEDULED';
    case IN_EXECUTION = 'IN EXECUTION';
    case COMPLETED = 'COMPLETED';
    case CLOSED = 'CLOSED';
}
