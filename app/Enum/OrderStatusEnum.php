<?php

namespace App\Enum;

enum OrderStatusEnum: string
{
    case PLANNED = 'PLANNED';
    case CONFIRMED = 'CONFIRMED';
    case EXECUTION = 'EXECUTION';
    case SUPERVISION = 'SUPERVISION';
    case INSPECTION = 'INSPECTION';
    case FINISH = 'FINISH';
    case FINAL_INSPECTION = 'FINAL INSPECTION';
    case FINAL_COLLECT = 'FINAL COLLECT';
}
