<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Command;

use PhilippHermes\TransferBundle\Service\TransferGenerator;
use PhilippHermes\TransferBundle\Service\XmlSchemaParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'transfer:generate', description: 'Generate Transfers from XML schemas')]
class TransferGenerateCommand extends Command
{
    /**
     * @param string|null $name
     * @param XmlSchemaParser $parser
     * @param TransferGenerator $generator
     */
    public function __construct(
        ?string $name = 'transfer:generate',
        protected readonly XmlSchemaParser $parser,
        protected readonly TransferGenerator $generator,
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
        $collection = $this->parser->parse();

        if ($collection->getTransfers()->count() === 0) {
            $output->writeln("<comment>No transfers found in configured dir</comment>");

            return Command::FAILURE;
        }

        $this->generator->generate($collection);

        $output->writeln("<info>✅ Transfers generated for configured dir</info>");

        return Command::SUCCESS;
    }
}
