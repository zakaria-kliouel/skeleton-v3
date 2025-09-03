<?php

declare(strict_types=1);

namespace App\Trait;

use Symfony\Component\String\UnicodeString;

trait StringUtilsTrait
{
    public function camelcase(string $string): string
    {
        return (new UnicodeString($string))->camel()->toString();
    }

    public function snakecase(string $string): string
    {
        return (new UnicodeString($string))->snake()->toString();
    }
}
