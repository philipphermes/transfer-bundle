<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service;

use PhilippHermes\TransferBundle\Service\Model\TransferGeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\XmlSchemaParserInterface;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

readonly class TransferService implements TransferServiceInterface
{
    /**
     * @param XmlSchemaParserInterface $xmlSchemaParser
     * @param TransferGeneratorInterface $transferGenerator
     */
    public function __construct(
        protected XmlSchemaParserInterface $xmlSchemaParser,
        protected TransferGeneratorInterface $transferGenerator,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function parse(): TransferCollectionTransfer
    {
        return $this->xmlSchemaParser->parse();
    }

    /**
     * @inheritDoc
     */
    public function generateTransfer(TransferTransfer $transfer): void
    {
        $this->transferGenerator->generateTransfer($transfer);
    }
}