<?php

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

interface ClassGeneratorInterface
{
    /**
     * @param TransferTransfer $transferTransfer
     * @param TransferCollectionTransfer $transferCollection
     * @param string $namespace
     *
     * @return string
     */
    public function generateClassHeader(TransferTransfer $transferTransfer, TransferCollectionTransfer $transferCollection, string $namespace): string;
}