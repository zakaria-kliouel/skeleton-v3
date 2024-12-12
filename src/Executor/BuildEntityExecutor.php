<?php

declare(strict_types=1);

namespace App\Executor;

use App\Enum\AppsEnum;
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
     * @param AppsEnum[] $apps
     * @param mixed[] $properties
     *
     * @return Generator<mixed>
     */
    public function execute(
        array $apps,
        string $entity,
        array $properties,
        ?bool $dryRun,
    ): Generator {
        foreach ($this->generators as $generator) {
            yield from $generator->generate($apps, $entity, $properties, $dryRun);
        }
    }
}
