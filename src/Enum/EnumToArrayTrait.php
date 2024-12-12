<?php

declare(strict_types=1);

namespace App\Enum;

use function array_column;

trait EnumToArrayTrait
{
    /**
     * @return string[]
     */
    public static function getNames(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * @return string[]|int[]|float[]
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
