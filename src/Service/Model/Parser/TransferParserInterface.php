<?php

namespace PhilippHermes\TransferBundle\Service\Model\Parser;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;

interface TransferParserInterface
{
    /**
     * @return TransferCollectionTransfer
     */
    public function parse(GeneratorConfigTransfer $generatorConfigTransfer): TransferCollectionTransfer;
}