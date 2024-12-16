<?php

namespace App\Enum;

use Faker\Core\Number;

enum PlaningDateSupervisorEnum: int
{
    case PROJECTS_WITHOUT_PERMISSIONS = 5 ;
    case PROJECTS_WITH_PERMISSIONS = 15 ;
    case COMMERCIAL_PROJECTS = 30 ;
}
