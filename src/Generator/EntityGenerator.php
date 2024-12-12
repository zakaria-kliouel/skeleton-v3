<?php

namespace App\Generator;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\MakerBundle\Generator as MakerGenerator;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Generator;

class EntityGenerator implements GeneratorInterface
{
    private MakerGenerator $generator;
    private LoggerInterface $logger;

    public function __construct(
        ?MakerGenerator $generator = null,
        LoggerInterface $logger,
    ) {
        if (null === $generator) {
            @trigger_error(\sprintf('Passing a "%s" instance as 4th argument is mandatory since version 1.5.', Generator::class), \E_USER_DEPRECATED);
            $this->generator = new MakerGenerator($fileManager, 'App\\');
        } else {
            $this->generator = $generator;
        }
    }

    public static function getPriority(): int
    {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function generate(
        array $apps,
        string $entity,
        array $properties,
        ?bool $dryRun,
    ): Generator {
        $entityClassDetails = $this->generator->createClassNameDetails(
            $entity,
            'Entity\\'
        );
        var_dump($entityClassDetails);
        die();
        $classFullName = $entityClassDetails->getFullName();
        $classExists = class_exists($classFullName);

        if ($classExists) {
            $entityPath = $this->getPathOfClass($classFullName);
            $this->logger->info('Your entity already exists! So let\'s add some new fields!');
        } else {
            $this->logger->info('Entity generated! Now let\'s add some fields!');
        }

        $currentFields = $this->getPropertyNames($classFullName);
        $manipulator = $this->createClassManipulator($entityPath, $io, $overwrite);

        $generator->writeChanges();

        yield ['type' => 'success', 'message' => $entity.'Entity created GG !'];
    }
    /** @return string[] */
    private function getPropertyNames(string $class): array
    {
        if (!class_exists($class)) {
            return [];
        }
        $reflClass = new \ReflectionClass($class);

        return array_map(static fn (\ReflectionProperty $prop) => $prop->getName(), $reflClass->getProperties());
    }

    private function createClassManipulator(string $path, bool $overwrite): ClassSourceManipulator
    {
        $manipulator = new ClassSourceManipulator(
            sourceCode: $this->fileManager->getFileContents($path),
            overwrite: $overwrite,
        );

        $manipulator->setIo($io);

        return $manipulator;
    }
}
