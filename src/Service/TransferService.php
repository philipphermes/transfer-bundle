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
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer): TransferCollectionTransfer
    {
        return $this->transferServiceFactory->createGenerator()->generate($generatorConfigTransfer);
    }
}