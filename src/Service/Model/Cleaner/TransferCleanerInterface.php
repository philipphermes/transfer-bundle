<?php

namespace PhilippHermes\TransferBundle\Service\Model\Cleaner;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;

interface TransferCleanerInterface
{
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