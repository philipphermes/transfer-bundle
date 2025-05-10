<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Command;

use PhilippHermes\TransferBundle\Service\TransferServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'transfer:generate', description: 'Generate Transfers from XML schemas')]
class TransferGenerateCommand extends Command
{
    /**
     * @param TransferServiceInterface $transferService
     * @param string|null $name
     */
    public function __construct(
        protected readonly TransferServiceInterface $transferService,
        protected readonly string $schemaDir,
        protected readonly string $outputDir,
        ?string $name = 'transfer:generate',
    )
    {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('transfer:generate')
            ->setDescription('generates transfers from xml schema');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Transfer Generator');

        $io->info(sprintf('Reading dir: %s', $this->schemaDir));

        $this->transferService->clean();
        $collection = $this->transferService->parse();

        if ($collection->getTransfers()->count() === 0) {
            $io->warning(sprintf('No transfers found in dir: %s', $this->schemaDir));

            return Command::FAILURE;
        }

        foreach ($collection->getTransfers() as $transfer) {
            $io->text(sprintf('Generating transfer: %s', $transfer->getName()));

            $this->transferService->generateTransfer($transfer, $collection);
        }

        $io->success(sprintf('Transfers generated in dir: %s', $this->outputDir));

        return Command::SUCCESS;
    }
}
