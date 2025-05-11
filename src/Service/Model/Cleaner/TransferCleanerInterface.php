<?php

namespace PhilippHermes\TransferBundle\Service\Model\Cleaner;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;

interface TransferCleanerInterface
{
    /**
     * @param GeneratorConfigTransfer $generatorConfigTransfer
     *
     * @return void
     */
    public function clean(GeneratorConfigTransfer $generatorConfigTransfer): void;
}