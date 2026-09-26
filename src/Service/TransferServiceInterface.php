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
     * @return array<string> paths of the written files
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer, TransferCollectionTransfer $transferCollectionTransfer, callable $progressCallback): array;

    /**
     * Removes generated transfers from the output directory, except the files in $keepFiles.
     *
     * @param GeneratorConfigTransfer $generatorConfigTransfer
     * @param array<string> $keepFiles
     *
     * @return void
     */
    public function clean(GeneratorConfigTransfer $generatorConfigTransfer, array $keepFiles = []): void;
}