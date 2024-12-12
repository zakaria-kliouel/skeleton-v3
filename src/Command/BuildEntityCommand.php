<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\AppsEnum;
use App\Enum\DatabaseEnum;
use App\Enum\JoinColumnEnum;
use App\Executor\BuildEntityExecutor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Throwable;


#[AsCommand(name: 'skeleton:build-entity')]
class BuildEntityCommand extends Command
{
    private $propertiesFormat = '{
          "propertyName": {
            "isIdentifier": false, // bool (vérifier au moins une propriété id dans la conf
            "databaseColumnName": "", // string
            "nullable": true, // bool
            "type": "", // string
            "defaultValue": "", // string
            "collectionType": "", // ?string (seulement si type = collection)
            "database": "avanis", // enum avanis, avanis_v2, bi, sage
            "joinColumn": { // ?array
              "type": "",  // enum OneToOne, OneToMany, ManyToOne
              "targetEntity": "", // string
              "mappedBy": "", // ?string
              "referencedColumnName": "", // ?string
              "cascade": "", // ?string (separated comma)
              "inversedBy": "", // ?string
            }
          }
    }';

    private readonly SymfonyStyle $io;

    /**
     * @var AppsEnum[]
     */
    private array $apps;

    private ?bool $dryRun;

    private string $entity;

    /**
     * @var mixed[]
     */
    private array $properties;
    public function __construct(private readonly BuildEntityExecutor $executor)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'apps',
            '-a',
            InputOption::VALUE_REQUIRED,
            'Application list (backoffice, frontoffice, tunnel) separated by comma.',
            'backoffice',
        )
        ->addOption(
            'dry-run',
            '-d',
            InputOption::VALUE_NONE,
            'Only lists files that would be created.',
        )
        ->addOption(
            'entity',
            '-en',
            InputOption::VALUE_REQUIRED,
            'Entity name without entity suffix',
        )
        ->addOption(
            'properties',
            '-p',
            InputOption::VALUE_REQUIRED,
            'Entity properties json format : '.$this->propertiesFormat,
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->dryRun = $input->getOption('dry-run');
        $this->apps = array_map(fn($app): AppsEnum => AppsEnum::from($app), explode(',',trim($input->getOption('apps'))));
        $this->entity = $input->getOption('entity');
        $this->properties = json_decode($input->getOption('properties'), true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $code = self::SUCCESS;
        if (!$this->validateProperties()) {
            $this->io->error('Properties validation failed');
            return self::FAILURE;
        }

        if ($this->apps !== ($supportedApps = AppsEnum::getSupportedApps())) {
            $this->io->error('Only : '.implode(',', array_column($supportedApps, 'value')).' apps supported.');
            return self::FAILURE;

        }

        try {
            foreach ($this->executor->execute($this->apps, $this->entity, $this->properties, $this->dryRun) as $result)
            {
                $this->io->{$result['type']}($result['message']);
            }
        } catch (Throwable $e) {
            $message = sprintf('%s On file : %s : %d', $e->getMessage(), $e->getFile(), $e->getLine());
            $code = self::FAILURE;
            $this->io->error($message);
        }

        return $code;
    }

    private function validateProperties(): bool
    {
        $validator = Validation::createValidator();

        $this->io->title('Properties Validation');

        $constraint = new Assert\Collection([
            'isIdentifier' => new Assert\Required([
                new Assert\Type('boolean'),
            ]),
            'databaseColumnName' => new Assert\Required([
                new Assert\Type('string'),
            ]),
            'nullable' => new Assert\Required([
                new Assert\Type('boolean'),
            ]),
            'type' => new Assert\Required([
                new Assert\Type('string'),
            ]),
            'defaultValue' => new Assert\Optional([
                new Assert\Type('string'),
            ]),
            'collectionType' => new Assert\Optional([
                new Assert\Type('string')
            ]),
            'database' => new Assert\Required([
                new Assert\Choice(
                    options: $options = DatabaseEnum::getValues(),
                    message: 'The value you selected is not a valid choice. Expected : '. implode(',', $options),
                )
            ]),
            'joinColumn' => new Assert\Optional([
                new Assert\Collection([
                    'type' => new Assert\Required([
                        new Assert\Choice(
                            options: $options = JoinColumnEnum::getValues(),
                            message: 'The value you selected is not a valid choice. Expected : '. implode(',', $options),
                        )
                    ]),
                    'targetEntity' => new Assert\Required([
                        new Assert\Type('string'),
                    ]),
                    'mappedBy' => new Assert\Optional([
                        new Assert\Type('string'),
                    ]),
                    'referencedColumnName' => new Assert\Optional([
                        new Assert\Type('string'),
                    ]),
                    'cascade' => new Assert\Optional([
                        new Assert\Type('string'),
                    ]),
                    'inversedBy' => new Assert\Optional([
                        new Assert\Type('string'),
                    ]),
                ]),

            ]),
        ]);

        foreach($this->properties as $propertyName => $property) {
            $this->io->section('Start validate '.$propertyName);
            $violations = $validator->validate($property, $constraint);
            if (0 === $violations->count()) {
                $this->io->info('Validation of '.$propertyName.' sucessfull.');
            } else {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = [
                        $violation->getPropertyPath(),
                        $violation->getMessage(),
                    ];
                }
                $this->io->table(['Property', 'error'], $errors);

                return false;
            }
        }

        return true;

    }

}
