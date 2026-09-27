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

    /**
     * Returns the generated transfers in the output directory that are not in $keepFiles.
     *
     * @param GeneratorConfigTransfer $generatorConfigTransfer
     * @param array<string> $keepFiles
     *
     * @return array<string> absolute paths
     */
    public function findStale(GeneratorConfigTransfer $generatorConfigTransfer, array $keepFiles = []): array;
}