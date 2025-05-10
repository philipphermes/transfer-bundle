<?php

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;

interface UserGeneratorInterface
{
    /**
     * @param array<array-key, PropertyTransfer> $sensitiveProperties
     * @param string $code
     *
     * @return string
     */
    public function generateEraseCredentials(array $sensitiveProperties, string $code): string;
}