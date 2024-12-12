<?php

declare(strict_types=1);

namespace App\Executor;

use App\Generator\GeneratorInterface;
use Generator;

class BuildEntityExecutor
{
    /**
     * @param iterable<GeneratorInterface> $generators
     */
    public function __construct(
        private iterable $generators,
    ) {
    }
    /**
     *
     * @return Generator<mixed>
     */
    public function execute(
        array $apps,
        string $entity,
        array $properties,
        ?bool $dryRun,
    ): void {
        foreach ($this->generators as $generator) {
            $generator->generate($apps, $entity, $properties, $dryRun);
        }
    }
}
