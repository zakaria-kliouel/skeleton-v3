<?php

namespace App\Generator;

use App\Enum\AppsEnum;
use Psr\Log\LoggerInterface;
use LogicException;
use Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\Yaml\Yaml;

class ApiFileGenerator implements GeneratorInterface
{

    /**
     * @var string[]
     */
    private array $replacements;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Filesystem $filesystem,
        private readonly EnglishInflector $inflector,
        private readonly string $publicPath,
        private readonly array $openApiFileResources,
    ) {
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
        $entityPluralize = $this->inflector->pluralize($entity)[0];
        $this->replacements = [
            '{{entities}}' => strtolower($entityPluralize),
            '{{entity}}' => strtolower($entity),
            '{{Entities}}' => ucfirst($entityPluralize),
            '{{Entity}}' => ucfirst($entity),
        ];
        foreach ($apps as $app) {
            $globalPath = $this->publicPath.'/'.$app->value.'/api/'.$app->value;
            $this->filesystem->mkdir([
                $resourcesPath = $globalPath.'/resources/'.$entityPluralize,
                $schemasPath = $globalPath.'/schemas/'.$entityPluralize
            ], 0755);

            $this->generateDefinitionsFile($globalPath);
            $this->generateSchemaFiles($properties, $schemasPath);
            $this->generateResourceFiles($properties, $resourcesPath);
        }

        yield ['type' => 'success', 'message' => $app->value.' '.$entity.' files for openApi created.'];

    }


    public static function getPriority(): int
    {
        return 20;
    }

    private function generateDefinitionsFile(string $globalPath): void
    {
        if (!$this->filesystem->exists($this->openApiFileResources['definitions'])) {
            throw new LogicException('Definitions template file not exist !');
        }

        file_put_contents(
            $globalPath.'/definitions.yaml',
            str_replace(
                array_keys($this->replacements),
                array_values($this->replacements),
                file_get_contents($this->openApiFileResources['definitions'])
            )
        );
    }

    /**
     * @param mixed[]  $properties
     */
    private function generateSchemaFiles(array $properties, string $schemasPath): void
    {
        if (!$this->filesystem->exists($this->openApiFileResources['readSchema'])) {
            throw new LogicException('Read schema template file not exist !');
        }

        if (!$this->filesystem->exists($this->openApiFileResources['writeSchema'])) {
            throw new LogicException('Write schema template file not exist !');
        }

        $readTemplate = file_get_contents($this->openApiFileResources['readSchema']);
        $readContent = Yaml::parse(str_replace(array_keys($this->replacements), array_values($this->replacements), $readTemplate));

        $writeTemplate = file_get_contents($this->openApiFileResources['writeSchema']);
        $writeContent = Yaml::parse(str_replace(array_keys($this->replacements), array_values($this->replacements), $writeTemplate));

        $ucFirstEntity = $this->replacements['{{Entity}}'];

        foreach ($properties as $key => $property) {
            $readContent[$ucFirstEntity]['properties'][$key] = [];
            if ($property['isIdentifier']) {
                $readContent[$ucFirstEntity]['properties'][$key]['readOnly'] = true;
            }

            $readContent[$ucFirstEntity]['properties'][$key]['type'] = $property['type'];

            if (false === $property['nullable']) {
                $readContent[$ucFirstEntity]['required'][] = $key;
            }

            if (false === $property['isIdentifier']) {
                $writeContent[$ucFirstEntity]['properties'][$key]['type'] = $property['type'];
                if (isset($property['defaultValue'])) {
                    $writeContent[$ucFirstEntity]['properties'][$key]['default'] = $property['defaultValue'];
                }

                if (true === $property['nullable']) {
                    $writeContent[$ucFirstEntity]['notRequired'][] = $key;
                }
            }
        }

        file_put_contents($schemasPath.'/read.yaml', preg_replace('/-\s*\n\s*/', '- ',Yaml::dump($readContent, 7, 2)));
        file_put_contents($schemasPath.'/write.yaml', preg_replace('/-\s*\n\s*/', '- ',Yaml::dump($writeContent, 7, 2)));
    }

    /**
     * @param mixed[]  $properties
     */
    private function generateResourceFiles(array $properties, string $resourcesPath): void
    {
        if (!$this->filesystem->exists($this->openApiFileResources['indexResource'])) {
            throw new LogicException('Index resource template file not exist !');
        }

        if (!$this->filesystem->exists($this->openApiFileResources['idResource'])) {
            throw new LogicException('Id resource template file not exist !');
        }

        $replacements = array_merge($this->replacements, ['{{identifier}}' => array_key_first(array_filter($properties, fn($item) => $item['isIdentifier'] === true))]);

        file_put_contents(
            $resourcesPath.'/_index.yaml',
            str_replace(
                array_keys($replacements),
                array_values($replacements),
                file_get_contents($this->openApiFileResources['indexResource'])
            )
        );
        file_put_contents(
            $resourcesPath.'/{id}.yaml',
            str_replace(
                array_keys($replacements),
                array_values($replacements),
                file_get_contents($this->openApiFileResources['idResource'])
            )
        );
    }
}
