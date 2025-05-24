<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Type;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use ReflectionClass;

class PropertyTypeMapper
{
    public const array PHP_TYPES = [
        'int', 'float', 'string', 'bool', 'array', 'object', 'mixed',
    ];

    /**
     * @var array<string, string>
     */
    protected static array $checkedObjects = [];

    /**
     * @param PropertyTransfer $propertyTransfer
     * @param string $type
     *
     * @return PropertyTransfer
     */
    public function addTypes(GeneratorConfigTransfer $generatorConfigTransfer, PropertyTransfer $propertyTransfer, string $type): PropertyTransfer
    {
        $propertyTransfer
            ->setType($this->getType($type))
            ->setSingularType($this->getSingularType($type));

        $propertyTransfer->setAnnotationType($this->getAnnotationType($propertyTransfer));
        $propertyTransfer->setSingularAnnotationType($this->getSingularAnnotationType($propertyTransfer));

        if (!in_array($propertyTransfer->getType(), self::PHP_TYPES, true)) {
            if (str_contains($propertyTransfer->getType(), 'Transfer')) {
                $propertyTransfer->setType($generatorConfigTransfer->getNamespace() . '\\' . $propertyTransfer->getType());
            }
        }

        if ($propertyTransfer->getSingularType() && !in_array($propertyTransfer->getSingularType(), self::PHP_TYPES, true)) {
            if (str_contains($propertyTransfer->getSingularType(), 'Transfer')) {
                $propertyTransfer->setSingularType($generatorConfigTransfer->getNamespace() . '\\' . $propertyTransfer->getSingularType());
            }
        }

        return $propertyTransfer;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getType(string $type): string
    {
        if (in_array($type, self::PHP_TYPES, true)) {
            return $type;
        }

        if (str_contains($type, '[]')) {
            $basicType = str_replace('[]', '', $type);
            if (in_array($basicType, self::PHP_TYPES, true)) {
                return 'array';
            }

            return 'ArrayObject';
        }

        return $this->extractType($type);
    }

    /**
     * @param string $type
     *
     * @return string|null
     */
    protected function getSingularType(string $type): ?string
    {
        if (str_contains($type, '[]')) {
            return $this->extractType(
                str_replace('[]', '', $type),
            );
        }

        return null;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function extractType(string $type): string
    {
        if (in_array($type, self::PHP_TYPES, true)) {
            return $type;
        }

        if (in_array($type, self::$checkedObjects, true)) {
            return self::$checkedObjects[$type];
        }

        try {
            /** @phpstan-ignore-next-line */
            $ref = new ReflectionClass($type);
            if ($ref->isInternal()) {
                return self::$checkedObjects[$type] = $type;
            } else {
                return self::$checkedObjects[$type] = $type . 'Transfer';
            }
        } catch (\ReflectionException) {
            return self::$checkedObjects[$type] = $type . 'Transfer';
        }
    }

    /**
     * @param PropertyTransfer $propertyTransfer
     *
     * @return string
     */
    public function getAnnotationType(PropertyTransfer $propertyTransfer): string
    {
        if ($propertyTransfer->getSingularType()) {
            return sprintf(
                '%s<array-key, %s>',
                $propertyTransfer->getType(),
                $propertyTransfer->getSingularType(),
            );
        }

        return $propertyTransfer->getType();
    }

    /**
     * @param PropertyTransfer $propertyTransfer
     *
     * @return string|null
     */
    public function getSingularAnnotationType(PropertyTransfer $propertyTransfer): ?string
    {
        if ($propertyTransfer->getSingularType()) {
            return $propertyTransfer->getSingularType();
        }

        return null;
    }
}