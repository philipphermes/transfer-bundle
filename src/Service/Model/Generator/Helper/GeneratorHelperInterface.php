<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\Helper;

use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

interface GeneratorHelperInterface
{
    /**
     * @param string $type
     * @param array<string> $transferTypes
     *
     * @return string
     */
    public function getPropertyType(string $type, array $transferTypes): string;

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

    /**
     * @param TransferTransfer $transferTransfer
     *
     * @return TransferTransfer
     */
    public function addRoleProperty(TransferTransfer $transferTransfer): TransferTransfer;
}