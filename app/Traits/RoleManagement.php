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
                  RoleEnum::$CLIENT_ADMIN,
                  RoleEnum::$CLIENT,
                  RoleEnum::$ACCOUNTING,
                  RoleEnum::$PRODUCTION
                ];
                break;
            case RoleEnum::$CLIENT_ADMIN:
                $roles = [
                  RoleEnum::$CLIENT
                ];
                break;
            case RoleEnum::$CLIENT:
                $roles = [];
                break;
        }
        return $roles;
    }

    function IsAdmin($role) {
        return $role == RoleEnum::$ADMIN;
    }
}