<?php

namespace App\Enum;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case ACCOUNT_MANAGER = 'account_manager';
    case INSTALLER = 'installer';
    case REMEASURER = 'remeasurer';
    case SUPERVISOR = 'supervisor';
    case OWNER = 'owner';
    case WAREHOUSE_MANAGER = 'warehouse_manager';
    case SERVICE_MANAGER = 'service_manager';
    case FRONTDESK = 'frontdesk';
    case ACCOUNTING = 'accounting';
    case PAYMENT_COORDINATOR = 'payment_coordinator';
    case OWNER_ADMIN = 'owner_admin';
    case FRONTDESK_ADMIN = 'frontdesk_admin';
    case FRONTDESK_ESR = 'frontdesk_esr';
    case CUSTOMER = 'customer';
}
