<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Command;

use PhilippHermes\TransferBundle\Service\TransferGenerator;
use PhilippHermes\TransferBundle\Service\XmlSchemaParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'transfer:generate', description: 'Generate Transfers from XML schemas')]
class TransferGenerateCommand extends Command
{
    /**
     * @param XmlSchemaParser $parser
     * @param TransferGenerator $generator
     * @param string|null $name
     */
    public function __construct(
        protected readonly XmlSchemaParser $parser,
        protected readonly TransferGenerator $generator,
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

        $io->info(sprintf('Searching dir: %s', $this->parser->schemaDir));

        $collection = $this->parser->parse();

        if ($collection->getTransfers()->count() === 0) {
            $io->warning(sprintf('No transfers found in dir: %s', $this->parser->schemaDir));

            return Command::FAILURE;
        }

        foreach ($collection->getTransfers() as $transfer) {
            $io->text(sprintf('Generating transfer: %s', $transfer->getName()));

            $this->generator->generateTransfer($transfer);
        }

        $io->success(sprintf('Transfers generated in dir: %s', $this->generator->outputDir));

        return Command::SUCCESS;
    }
}
