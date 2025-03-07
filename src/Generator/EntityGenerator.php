<?php

namespace App\Generator;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\MakerBundle\Generator as MakerGenerator;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Generator;

class EntityGenerator implements GeneratorInterface
{

    /**
     * @inheritDoc
     */
    public function generate(
        array $apps,
        string $entity,
        array $properties,
        ?bool $dryRun,
    ): Generator {

        yield ['type' => 'success', 'message' => $entity.'Entity created GG !'];
    }

    public static function getPriority(): int
    {
        return 1;
    }
}
