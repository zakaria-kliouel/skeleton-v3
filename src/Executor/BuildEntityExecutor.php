<?php

declare(strict_types=1);

namespace App\Executor;

use App\Enum\AppsEnum;
use App\Enum\DatabaseEnum;
use App\Generator\GeneratorInterface;
use Generator;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

class BuildEntityExecutor
{
    /**
     * @param iterable<GeneratorInterface> $generators
     */
    public function __construct(
        private iterable $generators,
        private Filesystem $filesystem,
        private string $publicPath,
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
        DatabaseEnum $database,
        string $entity,
        array $properties,
        ?bool $dryRun,
    ): Generator {
        try {
            foreach ($apps as $app) {
                $this->filesystem->mkdir($this->publicPath.'/'.$app->value, 0755);
                yield ['type' => 'success', 'message' => $app->value.' path created.'];
            }
        } catch (Throwable $exception) {
            yield ['type' => 'error', 'message' => 'Error on path generation.'];
        }

        foreach ($this->generators as $generator) {
            yield from $generator->generate($apps, $database, $entity, $properties, $dryRun);
        }
    }
}
