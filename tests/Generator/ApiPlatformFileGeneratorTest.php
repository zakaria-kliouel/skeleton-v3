<?php

declare(strict_types=1);

namespace App\Tests\Generator;

use App\Enum\AppsEnum;
use App\Enum\DatabaseEnum;
use App\Generator\ApiPlatformFileGenerator;
use Generator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;

class ApiPlatformFileGeneratorTest extends KernelTestCase
{
    private LoggerInterface $logger;
    private Filesystem $filesystem;

    private string $publicPath;

    /**
     * @var string[]
     */
    private array $apiPlatformTemplates;

    public function setUp(): void
    {
        self::bootKernel();

        $this->publicPath = self::$kernel->getContainer()->getParameter('public_dir');
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filesystem = new Filesystem();
        $this->apiPlatformTemplates = self::$kernel->getContainer()->getParameter('api_platform_file_resources');
    }

    public function testApiPlatformGenerate(): void
    {
        $generator = new ApiPlatformFileGenerator(
            $this->logger,
            $this->filesystem,
            $this->publicPath,
            $this->apiPlatformTemplates,
        );

        $results = $generator->generate(
            [AppsEnum::BACKOFFICE],
            DatabaseEnum::AVANIS,
            'Dummy',
            [
                'id' => [
                    'isIdentifier' => true,
                    'databaseColumnName' => 'id',
                    'nullable' => false,
                    'type' => 'integer',
                ],
                'firstProperty' => [
                    'isIdentifier' => false,
                    'databaseColumnName' => 'first_property',
                    'nullable' => false,
                    'type' => 'string',
                    'defaultValue' => 'default',
                ],
                'secondProperty' => [
                    'isIdentifier' => false,
                    'databaseColumnName' => 'second_property',
                    'nullable' => false,
                    'type' => 'boolean',
                ],
                'thirdProperty' => [
                    'isIdentifier' => false,
                    'databaseColumnName' => 'third_property',
                    'nullable' => true,
                    'type' => 'float',
                    'defaultValue' => 3.14,
                ],
            ],
            false,
        );

        $this->assertInstanceOf(Generator::class, $results);
        $this->assertEquals(
            [
                ['type' => 'success', 'message' => 'backoffice Dummy files for openApi created.'],
            ],
            iterator_to_array($results)
        );

        $this->assertFileEquals(
            '/app/tests/snapshot/ApiFile/api_platform/resources/avanis_v1/Dummy.yaml',
            $this->publicPath.'/backoffice/api/backoffice/api_platform/resources/avanis_v1/Dummy.yaml'
        );

        $this->assertFileEquals(
            '/app/tests/snapshot/ApiFile/api_platform/serialization/avanis_v1/Dummy.yaml',
            $this->publicPath.'/backoffice/api/backoffice/api_platform/serialization/avanis_v1/Dummy.yaml'
        );
    }

    public function testGetPriority(): void
    {
        $this->assertSame(20, ApiPlatformFileGenerator::getPriority());
    }
}
