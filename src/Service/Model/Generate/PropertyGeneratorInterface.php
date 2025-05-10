<?php

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

interface PropertyGeneratorInterface
{
    /**
     * @param TransferTransfer $transferTransfer
     * @param string $code
     *
     * @return string
     */
    public function generateProperties(TransferTransfer $transferTransfer, string $code): string;
}