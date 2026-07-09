<?php

namespace App\Enum;

enum ServiceControlStatusEnum: string
{
    case ORDER_IN_REVIEW = 'Order In Review';
    case MATERIAL_REVIEWED = 'Material Reviewed';
    case PRODUCTION = 'Production';
    case PRODUCTION_IN_PROGRESS = 'Production in Progress';
    case PRODUCTION_COMPLETED = 'Production Completed';
    case READY_FOR_DELIVERY = 'Ready for Delivery';
    case DELIVERED = 'Delivered';
    case COMPLETED = 'COMPLETED';
}
