<?php

declare(strict_types=1);

namespace App\Command;

use App\Executor\BuildEntityExecutor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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

    /**
     * @var string[]
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
            'Application list separated by comma',
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
        $this->dryRun = $input->getOption('dry-run');
        $this->apps = explode(',',trim($input->getOption('apps')));
        $this->entity = $input->getOption('entity');
        $this->properties = json_decode($input->getOption('properties'), true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $code = self::SUCCESS;

        try {
            foreach ($this->executor->execute($this->apps, $this->entity, $this->properties, $this->dryRun) as $result)
            {
                $io->{$result['type']}($result['message']);
            }
        } catch (Throwable $e) {
            $message = sprintf('%s On file : %s : %d', $e->getMessage(), $e->getFile(), $e->getLine());
            $code = self::FAILURE;
            $io->error($message);
        }

        return $code;
    }
}
