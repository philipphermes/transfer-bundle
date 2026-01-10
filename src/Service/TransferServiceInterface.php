<?php

namespace PhilippHermes\TransferBundle\Service;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;

interface TransferServiceInterface
{
    /**
     * @param GeneratorConfigTransfer $generatorConfigTransfer
     *
     * @return TransferCollectionTransfer
     */
    public function parse(GeneratorConfigTransfer $generatorConfigTransfer): TransferCollectionTransfer;

    /**
     * @param GeneratorConfigTransfer $generatorConfigTransfer
     * @param TransferCollectionTransfer $transferCollectionTransfer
     * @param callable $progressCallback
     *
     * @return void
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer, TransferCollectionTransfer $transferCollectionTransfer, callable $progressCallback): void;

    /**
     * @param GeneratorConfigTransfer $generatorConfigTransfer
     *
     * @return void
     */
    public function clean(GeneratorConfigTransfer $generatorConfigTransfer): void;
}