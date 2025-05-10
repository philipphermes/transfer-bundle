<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;

class GeneratorHelper implements GeneratorHelperInterface
{
    /**
     * @inheritDoc
     */
    public function getPropertyType(string $type, TransferCollectionTransfer $transferCollectionTransfer): string
    {
        if ($this->isArrayType($type)) {
            $propertyType = rtrim($type, '[]');

            return $this->isBasicType($propertyType) ? 'array' : 'ArrayObject';
        }

        if ($this->isBasicType($type)) {
            return strtolower($type);
        }

        foreach ($transferCollectionTransfer->getTransfers() as $transfer) {
            if ($transfer->getName() === $type) {
                return $transfer->getName() . 'Transfer';
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
}