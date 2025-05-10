<?php

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

interface PropertyGeneratorInterface
{
    /**
     * @param TransferTransfer $transferTransfer
     * @param TransferCollectionTransfer $transferCollection
     * @param string $code
     *
     * @return string
     */
    public function generateProperties(TransferTransfer $transferTransfer, TransferCollectionTransfer $transferCollection, string $code): string;
}