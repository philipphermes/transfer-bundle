<?php

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;

interface GetterGeneratorInterface
{
    /**
     * @param PropertyTransfer $property
     * @param TransferCollectionTransfer $transferCollection
     * @param string $code
     * @param string|null $overwriteMethodName
     *
     * @return string
     */
    public function generateGetter(PropertyTransfer $property, TransferCollectionTransfer $transferCollection, string $code, ?string $overwriteMethodName = null): string;
}