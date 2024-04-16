<?php

namespace App\Traits;

use App\Enum\RoleEnum;

trait RoleManagement {  
    function GetChildRolesByRole($role) {
        $roles = [];
        switch ($role) {
            case RoleEnum::$ADMIN:
                $roles = [
                  RoleEnum::$ADMIN,
                  RoleEnum::$ACCOUNT_MANAGER,
                  RoleEnum::$DEALER,
                  RoleEnum::$SUB_DEALER,
                  RoleEnum::$ACCOUNTING,
                  RoleEnum::$PRODUCTION,
                  RoleEnum::$PLANT_MANAGER,
                  RoleEnum::$SHIPPING,
                ];
                break;
            case RoleEnum::$ACCOUNT_MANAGER:
                $roles = [
                  RoleEnum::$DEALER,
                  RoleEnum::$SUB_DEALER,
                  RoleEnum::$ACCOUNTING,
                  RoleEnum::$PRODUCTION,
                  RoleEnum::$PLANT_MANAGER,
                  RoleEnum::$SHIPPING,
                ];
              break;
            case RoleEnum::$DEALER:
                $roles = [
                  RoleEnum::$SUB_DEALER
                ];
                break;
            default:
                $roles = [];
                break;
        }
        return $roles;
    }

    function IsAdmin($role) {
        return $role == RoleEnum::$ADMIN;
    }
}