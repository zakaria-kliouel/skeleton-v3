<?php

namespace App\Generator;

use App\Enum\AppsEnum;
use App\Enum\DatabaseEnum;
use Generator;

interface GeneratorInterface
{
    /**
     * @param AppsEnum[] $apps
     * @param mixed[] $properties
     *
     * @return Generator<mixed>
     */
    public function generate(
        array $apps,
        DatabaseEnum $database,
        string $entity,
        array $properties,
        ?bool $dryRun,
    ): Generator;

    public static function getPriority(): int;
}
