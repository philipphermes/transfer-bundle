<?php

namespace PhilippHermes\TransferBundle\Service;

use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

interface TransferServiceInterface
{
    /**
     * @return TransferCollectionTransfer
     */
    public function parse(): TransferCollectionTransfer;

    /**
     * @param TransferTransfer $transfer
     *
     * @return void
     */
    public function generateTransfer(TransferTransfer $transfer): void;
}