<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

interface GeneratorHelperInterface
{
    /**
     * @param string $type
     *
     * @return string
     */
    public function getPropertyType(string $type): string;

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