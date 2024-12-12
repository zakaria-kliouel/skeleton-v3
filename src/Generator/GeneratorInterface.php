<?php

namespace App\Generator;

interface GeneratorInterface
{
    public function generate(
        array $apps,
        string $entity,
        array $properties,
        ?bool $dryRun,
    ): void;

    public static function getPriority(): int;
}
