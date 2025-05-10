<?php

namespace PhilippHermes\TransferBundle\Service\Model;

use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

interface TransferGeneratorInterface
{
    /**
     * @param TransferTransfer $transfer
     * @param TransferCollectionTransfer $transferCollection
     *
     * @return void
     */
    public function generateTransfer(TransferTransfer $transfer, TransferCollectionTransfer $transferCollection): void;
}