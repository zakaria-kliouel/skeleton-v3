<?php

declare(strict_types=1);

namespace App\Enum;

enum AppsEnum: string
{
    use EnumToArrayTrait;

    case BACKOFFICE = 'backoffice';
    case FRONTOFFICE = 'frontoffice';
    case TUNNEL = 'tunnel';

    /**
     * @return string[]|int[]|float[]
     */
    public static function getSupportedApps(): array
    {
        return [self::BACKOFFICE];
    }
}
