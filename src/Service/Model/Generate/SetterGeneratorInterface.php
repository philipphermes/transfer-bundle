<?php

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;

interface SetterGeneratorInterface
{
    /**
     * @param PropertyTransfer $property
     * @param TransferCollectionTransfer $transferCollection
     * @param string $code
     *
     * @return string
     */
    public function generateSetter(PropertyTransfer $property, TransferCollectionTransfer $transferCollection, string $code): string;

    /**
     * @param PropertyTransfer $property
     * @param TransferCollectionTransfer $transferCollection
     * @param string $code
     *
     * @return string
     */
    public function generateAdder(PropertyTransfer $property, TransferCollectionTransfer $transferCollection, string $code): string;
}