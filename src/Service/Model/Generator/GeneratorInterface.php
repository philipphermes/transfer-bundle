<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;

interface GeneratorInterface
{
    /**
     * @param GeneratorConfigTransfer $generatorConfigTransfer
     *
     * @return TransferCollectionTransfer
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer): TransferCollectionTransfer;
}