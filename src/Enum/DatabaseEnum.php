<?php

declare(strict_types=1);

namespace App\Enum;

enum DatabaseEnum: string
{
    use EnumToArrayTrait;

    case AVANIS = 'avanis';
    case AVANISV2 = 'avanis_v2';
    case BI = 'bi';
    case sage = 'sage';
}
