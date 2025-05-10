<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

class GeneratorHelper implements GeneratorHelperInterface
{
    /**
     * @param string $type
     *
     * @return string
     */
    public function getPropertyType(string $type): string
    {
        if ($this->isArrayType($type)) {
            $propertyType = rtrim($type, '[]');

            return $this->isBasicType($propertyType) ? 'array' : 'ArrayObject';
        }

        return $this->isBasicType($type) ? strtolower($type) : $type;
    }

    /**
     * @param string $type
     * @param string $propertyType
     *
     * @return string
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
     * @param string $type
     *
     * @return bool
     */
    public function isBasicType(string $type): bool
    {
        return in_array(strtolower($type), ['int', 'string', 'bool', 'float', 'mixed', 'object'], true);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isArrayType(string $type): bool
    {
        return str_ends_with($type, '[]');
    }
}