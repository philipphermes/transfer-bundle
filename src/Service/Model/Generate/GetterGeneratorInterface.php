<?php

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;

interface GetterGeneratorInterface
{
    /**
     * @param PropertyTransfer $property
     * @param string $code
     * @param string|null $overwriteMethodName
     *
     * @return string
     */
    public function generateGetter(PropertyTransfer $property, string $code, ?string $overwriteMethodName = null): string;
}