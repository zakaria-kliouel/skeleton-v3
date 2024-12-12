<?php

declare(strict_types=1);

namespace App\Enum;

enum JoinColumnEnum: string
{
    use EnumToArrayTrait;

    case ONE_TO_ONE = 'oneToOne';
    case ONE_TO_MANY = 'oneToMany';
    case MANY_TO_ONE = 'manyToOne';
    case MANY_TO_MANY = 'manyToMany';
}
