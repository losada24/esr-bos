<?php

namespace App\Enum;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case ACCOUNT_MANAGER = 'account_manager';
    case INSTALLER = 'installer';
    case SUPERVISOR = 'supervisor';
    case OWNER = 'owner';
    case WAREHOUSE_MANAGER = 'warehouse_manager';
    case SERVICE_MANAGER = 'service_manager';
    case FRONTDESK = 'frontdesk';
    case ACCOUNTING = 'accounting';
}
