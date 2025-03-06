<?php

declare(strict_types=1);

namespace App\Tests\Generator;

use App\Enum\AppsEnum;
use App\Generator\ApiFileGenerator;
use Generator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Inflector\EnglishInflector;

class ApiFileGeneratorTest extends KernelTestCase
{
    private LoggerInterface $logger;
    private Filesystem $filesystem;

    private EnglishInflector $inflector;

    private string $publicPath;

    /**
     * @var string[]
     */
    private array $templates;

    public function setUp(): void
    {
        self::bootKernel();

        $this->publicPath = self::$kernel->getContainer()->getParameter('public_dir');
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filesystem = new Filesystem();
        $this->inflector = new EnglishInflector();
        $this->templates = self::$kernel->getContainer()->getParameter('open_api_file_resources');
    }

    public function testGenerate(): void
    {
        $generator = new ApiFileGenerator(
            $this->logger,
            $this->filesystem,
            $this->inflector,
            $this->publicPath,
            $this->templates,
        );

        $results = $generator->generate(
            [AppsEnum::BACKOFFICE],
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
            '/app/tests/snapshot/ApiFile/Dummies/definitions.yaml',
            $this->publicPath.'/backoffice/api/backoffice/definitions.yaml'
        );

        $this->assertFileEquals(
            '/app/tests/snapshot/ApiFile/Dummies/schemas/read.yaml',
            $this->publicPath.'/backoffice/api/backoffice/schemas/Dummies/read.yaml'
        );

        $this->assertFileEquals(
            '/app/tests/snapshot/ApiFile/Dummies/schemas/write.yaml',
            $this->publicPath.'/backoffice/api/backoffice/schemas/Dummies/write.yaml'
        );

        $this->assertFileEquals(
            '/app/tests/snapshot/ApiFile/Dummies/resources/_index.yaml',
            $this->publicPath.'/backoffice/api/backoffice/resources/Dummies/_index.yaml'
        );

        $this->assertFileEquals(
            '/app/tests/snapshot/ApiFile/Dummies/resources/{id}.yaml',
            $this->publicPath.'/backoffice/api/backoffice/resources/Dummies/{id}.yaml'
        );
    }

    public function testGetPriority(): void
    {
        $this->assertSame(20, ApiFileGenerator::getPriority());
    }
}
