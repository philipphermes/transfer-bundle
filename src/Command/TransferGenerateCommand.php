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

        $io->info(sprintf('Searching dirs: %s', $this->generatorConfig->getSchemaDirectory()));

        $transferCollectionTransfer = $this->transferService->parse($this->generatorConfig);

        if ($transferCollectionTransfer->getErrors()) {
            foreach ($transferCollectionTransfer->getErrors() as $error) {
                $io->error($error);
            }

            return Command::FAILURE;
        }

        if (!$input->getOption(self::OPTION_DISABLE_CLEAN)) {
            $this->transferService->clean($this->generatorConfig);
        }

        $generatingTransfersMessage = array_map(
            fn (TransferTransfer $transferTransfer) => '* ' . $transferTransfer->getName(),
            $transferCollectionTransfer->getTransfers()->getArrayCopy(),
        );

        array_unshift(
            $generatingTransfersMessage,
            sprintf('Generating transfers:')
        );

        $io->info($generatingTransfersMessage);

        $this->transferService->generate($this->generatorConfig, $transferCollectionTransfer);

        $io->success(sprintf('Transfers generated in dir: %s', $this->generatorConfig->getOutputDirectory()));

        return Command::SUCCESS;
    }
}
