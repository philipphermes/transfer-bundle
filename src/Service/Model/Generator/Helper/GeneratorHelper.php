<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\Helper;

use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class GeneratorHelper implements GeneratorHelperInterface
{
    /**
     * @inheritDoc
     */
    public function getPropertyType(string $type, array $transferTypes): string
    {
        if ($this->isArrayType($type)) {
            $propertyType = rtrim($type, '[]');

            return $this->isBasicType($propertyType) ? 'array' : 'ArrayObject';
        }

        if ($this->isBasicType($type)) {
            return strtolower($type);
        }

        foreach ($transferTypes as $transferType) {
            if ($transferType === $type) {
                return $transferType . 'Transfer';
            }
        }

        return $type;
    }

    /**
     * @inheritDoc
     */
    public function getPropertyAnnotationType(string $type, string $propertyType): string
    {
        if ($propertyType === 'array' || $propertyType === 'ArrayObject') {
            return sprintf(
                '%s<array-key, %s>',
                $propertyType,
                $propertyType === 'ArrayObject' ? rtrim($type, '[]') . 'Transfer' : strtolower($type === 'array' ? 'mixed' : rtrim($type, '[]'))
            );
        }

        return $propertyType;
    }

    /**
     * @inheritDoc
     */
    public function isBasicType(string $type): bool
    {
        return in_array(strtolower($type), ['int', 'string', 'bool', 'float', 'mixed', 'object'], true);
    }

    /**
     * @inheritDoc
     */
    public function isArrayType(string $type): bool
    {
        return str_ends_with($type, '[]');
    }

    /**
     * @inheritDoc
     */
    public function addRoleProperty(TransferTransfer $transferTransfer): TransferTransfer
    {
        if ($transferTransfer->getType() !== 'user') {
            return $transferTransfer;
        }

        foreach ($transferTransfer->getProperties() as $property) {
            if ($property->getName() === 'roles') {
                return $transferTransfer;
            }
        }

        $transferTransfer->addProperty((new PropertyTransfer())
            ->setName('roles')
            ->setSingular('role')
            ->setType('string[]')
            ->setIsNullable(false)
            ->setDescription(null)
            ->setIsIdentifier(false)
            ->setIsSensitive(false),
        );

        return $transferTransfer;
    }
}