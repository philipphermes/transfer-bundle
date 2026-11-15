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
    public function render(GeneratorConfigTransfer $generatorConfigTransfer, TransferCollectionTransfer $transferCollectionTransfer): array
    {
        return $this->transferServiceFactory->createGenerator()->render($generatorConfigTransfer, $transferCollectionTransfer);
    }

    /**
     * @inheritDoc
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer, TransferCollectionTransfer $transferCollectionTransfer, callable $progressCallback): array
    {
        return $this->transferServiceFactory->createGenerator()->generate($generatorConfigTransfer, $transferCollectionTransfer, $progressCallback);
    }

    /**
     * @inheritDoc
     */
    public function clean(GeneratorConfigTransfer $generatorConfigTransfer, array $keepFiles = []): void
    {
        $this->transferServiceFactory->createTransferCleaner()->clean($generatorConfigTransfer, $keepFiles);
    }

    /**
     * @inheritDoc
     */
    public function findStale(GeneratorConfigTransfer $generatorConfigTransfer, array $keepFiles = []): array
    {
        return $this->transferServiceFactory->createTransferCleaner()->findStale($generatorConfigTransfer, $keepFiles);
    }
}