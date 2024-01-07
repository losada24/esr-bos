<?php

namespace App\Enum;

class OrderStatusEnum
{
    public static $ESTIMATE = 'estimate';
    public static $ACCOUNTING = 'accounting';
    public static $PRODUCTION = 'production';
    public static $PRODUCTION_COMPLETED = 'production completed';
    public static $PARTIAL_PRODUCTION_COMPLETED = 'partial production completed';
    public static $READY_FOR_DELIVERY = 'ready for delivery';
    public static $ORDER_COMPLETED = 'order completed';
    public static $DELIVERED = 'delivered';
    public static $PARTIAL_DELIVERED = 'partial delivered';
}
