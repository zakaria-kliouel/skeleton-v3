<?php

declare(strict_types=1);

namespace App\Enum;

enum AppsEnum: string
{
    case BACKOFFICE = 'backoffice';
    case FRONTOFFICE = 'frontofffice';
    case TUNNEL = 'tunnel';
}

