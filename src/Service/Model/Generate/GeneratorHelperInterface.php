<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;

interface GeneratorHelperInterface
{
    /**
     * @param string $type
     * @param TransferCollectionTransfer $transferCollectionTransfer
     *
     * @return string
     */
    public function getPropertyType(string $type, TransferCollectionTransfer $transferCollectionTransfer): string;

    /**
     * @param string $type
     * @param string $propertyType
     *
     * @return string
     */
    public function getPropertyAnnotationType(string $type, string $propertyType): string;

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isBasicType(string $type): bool;

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isArrayType(string $type): bool;
}