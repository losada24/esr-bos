<?php

namespace App\Enum;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case ACCOUNT_MANAGER = 'account_manager';
    case INSTALLER = 'installer';
    case SUPERVISOR = 'supervisor';
    case OWNER = 'owner';
}
