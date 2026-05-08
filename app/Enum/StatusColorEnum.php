<?php

namespace App\Enum;

enum StatusColorEnum: string
{
    case PLANNED = '#9333ff';
    case PLANNED_INSTALLATION = '#5FE3FB';
    case PLANNED_INSTALLATION_EVENT = '#0a7bd1';
    case CONFIRMED = '#F4F443';
    case CONFIRMED_INSTALLATION = '#ffb533';
    case CONFIRMED_DELIVERY = '#FF8D33';
    case DELAY_PERMITS = '#AC0505';
    case COMPLETE = '#72cb10';
    case ON_HOLD = '#D3D3D3';
    case RESCHEDULE = '#cb4c08';
    case REPLANNED = '#db2777';
    case INSPECTION = '#7409EC';
    case FINISH = '#F5F4F4';
    case SERVICE = '#052f88';
    case FINAL_COLLECT = '#F3040E';
    case FINAL_INSPECTION = '#FBCC0E';
    case SUPERVISION = '#9A4F08';
    case EXECUTION = '#E47207';
    case MATERIALS_RECEIVED = '#008080';
    case CANCELED = '#700409';
    
}
