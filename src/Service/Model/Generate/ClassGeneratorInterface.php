<?php

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

interface ClassGeneratorInterface
{
    /**
     * @param TransferTransfer $transferTransfer
     * @param string $namespace
     *
     * @return string
     */
    public function generateClassHeader(TransferTransfer $transferTransfer, string $namespace): string;
}