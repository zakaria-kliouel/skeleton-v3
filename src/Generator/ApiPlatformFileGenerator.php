<?php

declare(strict_types=1);

namespace App\Generator;

use App\Enum\DatabaseEnum;
use App\Trait\StringUtilsTrait;
use Psr\Log\LoggerInterface;
use LogicException;
use Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ApiPlatformFileGenerator implements GeneratorInterface
{
    use StringUtilsTrait;

    /**
     * @var string[]
     */
    private array $replacements;

    private string $databaseName;
    private string $entityName;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Filesystem $filesystem,
        private readonly string $publicPath,
        private readonly array $apiPlatformFileResources,
    ) {
    }

    public function generate(
        array $apps,
        DatabaseEnum $database,
        string $entity,
        array $properties,
        ?bool $dryRun,
    ): Generator {
        $this->databaseName = ucfirst($this->camelcase((DatabaseEnum::AVANIS === $database) ? 'avanis_v1' : $database->value));
        $this->entityName = ucfirst($entity);
        $this->replacements = [
            '{{Entity}}' => $this->entityName,
            '{{Database}}' => $this->databaseName,
        ];

        foreach ($apps as $app) {
            $globalPath = $this->publicPath.'/'.$app->value.'/api/'.$app->value.'/api_platform';

            $this->filesystem->mkdir([
                $resourcesPath = $globalPath.'/resources/'.$this->snakecase($this->databaseName),
                $serializationPath = $globalPath.'/serialization/'.$this->snakecase($this->databaseName),
            ], 0755);

            $this->generateApiPlatformResourcesFiles($resourcesPath);
            $this->generateApiPlatformSerializationFiles($properties, $serializationPath);

            yield ['type' => 'success', 'message' => $app->value.' '.$entity.' files for openApi created.'];
        }


    }

    public static function getPriority(): int
    {
        return 20;
    }

    /**
     * @param mixed[]  $properties
     */
    private function generateApiPlatformResourcesFiles(string $resourcesPath): void
    {
        if (!$this->filesystem->exists($this->apiPlatformFileResources['resource'])) {
            throw new LogicException('Api platform resource template file not exist !');
        }

        $resourceTemplate = file_get_contents($this->apiPlatformFileResources['resource']);
        $resourceContent = Yaml::parse(str_replace(array_keys($this->replacements), array_values($this->replacements), $resourceTemplate));

        file_put_contents(
            $resourcesPath.'/'.$this->entityName.'.yaml',
            $this->getFormatedFileContent(Yaml::dump($resourceContent, 4, 2, Yaml::DUMP_NULL_AS_TILDE)),
        );
    }

    /**
     * @param mixed[]  $properties
     */
    private function generateApiPlatformSerializationFiles(array $properties, string $serializationPath): void
    {
        if (!$this->filesystem->exists($this->apiPlatformFileResources['serialization'])) {
            throw new LogicException('Api platform serialization template file not exist !');
        }

        $serializationTemplate = file_get_contents($this->apiPlatformFileResources['serialization']);
        $serializationContent = Yaml::parse(str_replace(array_keys($this->replacements), array_values($this->replacements), $serializationTemplate));

        $entityFullName = 'Common\Entity\\'.$this->databaseName.'\\' . $this->entityName;

        foreach ($properties as $key => $property) {
            $serializationContent[$entityFullName]['attributes'][$key]['groups'] = ($property['isIdentifier']) ? ['read'] : ['read', 'write'];
        }

        file_put_contents(
            $serializationPath.'/'.$this->entityName.'.yaml',
            $this->getFormatedFileContent(Yaml::dump($serializationContent, 4, 2)),
        );
    }

    private function getFormatedFileContent(string $content): string
    {
        $patterns = [
            '/\[/',
            '/]/',
            '/, /',
        ];

        $replacements = [
            '[ \'',
            '\' ]',
            '\', \''
        ];

        return preg_replace($patterns, $replacements, $content);
    }
}
