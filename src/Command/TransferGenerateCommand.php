<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Command;

use PhilippHermes\TransferBundle\Service\TransferServiceInterface;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'transfer:generate', description: 'Generate Transfers from XML schemas')]
class TransferGenerateCommand extends Command
{
    protected const string OPTION_DISABLE_CLEAN = 'clean-disable';

    protected GeneratorConfigTransfer $generatorConfig;

    /**
     * @param TransferServiceInterface $transferService
     * @param string $schemaDir
     * @param string $outputDir
     * @param string $namespace
     */
    public function __construct(
        protected readonly TransferServiceInterface $transferService,
        string $schemaDir,
        string $outputDir,
        string $namespace,
    )
    {
        parent::__construct();

        $this->generatorConfig = (new GeneratorConfigTransfer())
            ->setSchemaDirectory($schemaDir)
            ->setOutputDirectory($outputDir)
            ->setNamespace($namespace);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('transfer:generate')
            ->setDescription('generates transfers from xml schema')
            ->addOption(self::OPTION_DISABLE_CLEAN, mode: InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Transfer Generator');

        $io->section('Configuration');
        $io->definitionList(
            ['Schema Directory' => $this->generatorConfig->getSchemaDirectory()],
            ['Output Directory' => $this->generatorConfig->getOutputDirectory()],
            ['Namespace' => $this->generatorConfig->getNamespace()],
            ['Clean Output Dir' => !$input->getOption(self::OPTION_DISABLE_CLEAN)],
        );

        $io->section('Parsing Schemas');
        $transferCollectionTransfer = $this->transferService->parse($this->generatorConfig);

        if ($transferCollectionTransfer->getErrors()) {
            $io->error('Errors encountered while parsing schemas');

            foreach ($transferCollectionTransfer->getErrors() as $error) {
                $io->writeln(' - ' . $error);
            }

            return Command::FAILURE;
        }

        if (!$input->getOption(self::OPTION_DISABLE_CLEAN)) {
            $io->info('Cleaning output directory');
            $this->transferService->clean($this->generatorConfig);
        }

        $transfers = $transferCollectionTransfer->getTransfers()->getArrayCopy();

        $io->section(sprintf('Generating %d Transfers', count($transfers)));
        $io->listing(array_map(
            fn (TransferTransfer $t) => $t->getName(),
            $transfers,
        ));

        $progressBar = $io->createProgressBar(count($transfers));
        $progressBar->start();

        $this->transferService->generate(
            $this->generatorConfig,
            $transferCollectionTransfer,
            fn () => $progressBar->advance(),
        );

        $progressBar->finish();
        $io->newLine(2);

        $io->success('Transfer generation completed successfully');

        return Command::SUCCESS;
    }
}
