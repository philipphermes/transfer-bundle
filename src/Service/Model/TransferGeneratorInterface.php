<?php

namespace PhilippHermes\TransferBundle\Service\Model;

use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

interface TransferGeneratorInterface
{
    /**
     * @param TransferTransfer $transfer
     *
     * @return void
     */
    public function generateTransfer(TransferTransfer $transfer): void;
}