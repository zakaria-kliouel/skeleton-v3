<?php

declare(strict_types=1);

namespace App\Executor;

use App\Enum\AppsEnum;
use Generator;

class BuildEntityExecutor
{
    /**
     * @param AppsEnum[] $apps
     * @param mixed[]    $properties
     *
     * @return Generator<mixed>
     */
    public function execute(
        array $apps,
        string $entity,
        array $properties,
        ?bool $dryRun,
    ): Generator {

        yield ['type' => 'success', 'message' => $entity.' files created GG !'];
    }
}
