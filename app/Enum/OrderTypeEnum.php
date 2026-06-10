<?php

namespace App\Enum;

enum OrderTypeEnum: string
{
    case RESIDENTIAL = 'RESIDENTIAL';
    case COMMERCIAL = 'COMMERCIAL';
    case SUPPLY = 'SUPPLY';

}