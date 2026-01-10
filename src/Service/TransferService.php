<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;

readonly class TransferService implements TransferServiceInterface
{
    /**
     * @param TransferServiceFactory $transferServiceFactory
     */
    public function __construct(
        protected TransferServiceFactory $transferServiceFactory,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function parse(GeneratorConfigTransfer $generatorConfigTransfer): TransferCollectionTransfer
    {
        return $this->transferServiceFactory->createTransferParser()->parse($generatorConfigTransfer);
    }

    /**
     * @inheritDoc
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer, TransferCollectionTransfer $transferCollectionTransfer, callable $progressCallback): void
    {
        $this->transferServiceFactory->createGenerator()->generate($generatorConfigTransfer, $transferCollectionTransfer, $progressCallback);
    }

    /**
     * @inheritDoc
     */
    public function clean(GeneratorConfigTransfer $generatorConfigTransfer): void
    {
        $this->transferServiceFactory->createTransferCleaner()->clean($generatorConfigTransfer);
    }
}