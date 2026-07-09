<?php

namespace App\Enum;

enum ServiceControlTypeEnum: string
{
    case GLASS = 'GLASS';
    case ACCESSORIES = 'ACCESSORIES';
    case SCREENS = 'SCREENS';
    case FABRICATION = 'FABRICATION';
    case PANEL_FABRICATION = 'PANEL FABRICATION';
    case COVERS = 'COVERS';
    case SERVICE_MAN = 'SERVICE MAN';
    case MUNTINS = 'MUNTINS';
    case DELIVERY = 'DELIVERY';
}
