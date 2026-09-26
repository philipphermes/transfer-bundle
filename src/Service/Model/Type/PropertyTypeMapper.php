<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Type;

use InvalidArgumentException;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use ReflectionClass;

class PropertyTypeMapper
{
    public const array PHP_TYPES = [
        'int', 'float', 'string', 'bool', 'array', 'object', 'mixed',
    ];

    /**
     * @var array<string, true>
     */
    protected array $definedTransfers = [];

    /**
     * @param TransferCollectionTransfer|null $transferCollectionTransfer
     * @return void
     */
    public function setDefinedTransfers(?TransferCollectionTransfer $transferCollectionTransfer): void
    {
        $this->definedTransfers = [];
        if ($transferCollectionTransfer) {
            foreach ($transferCollectionTransfer->getTransfers() as $transfer) {
                $this->definedTransfers[$transfer->getName()] = true;
            }
        }
    }

    /**
     * Resolves the XML type of a property:
     * - PHP types are kept as-is
     * - defined transfers become `{namespace}\{Name}Transfer`
     * - existing classes, interfaces and enums are kept as FQCN
     * - `X[]` becomes `array` for PHP types and `ArrayObject` otherwise
     *
     * @param GeneratorConfigTransfer $generatorConfigTransfer
     * @param PropertyTransfer $propertyTransfer
     * @param string $type
     *
     * @throws InvalidArgumentException if the type can not be resolved
     *
     * @return PropertyTransfer
     */
    public function addTypes(GeneratorConfigTransfer $generatorConfigTransfer, PropertyTransfer $propertyTransfer, string $type): PropertyTransfer
    {
        $type = trim($type);

        if (str_ends_with($type, '[]')) {
            [$singularType, $isSingularTransfer] = $this->resolveType($generatorConfigTransfer, substr($type, 0, -2));

            $propertyTransfer
                ->setType(in_array($singularType, self::PHP_TYPES, true) ? 'array' : 'ArrayObject')
                ->setIsTransfer(false)
                ->setSingularType($singularType)
                ->setIsSingularTransfer($isSingularTransfer)
                ->setSingularAnnotationType($this->getAnnotation($singularType, $isSingularTransfer));

            $propertyTransfer->setAnnotationType(sprintf(
                '%s<array-key, %s>',
                $propertyTransfer->getType(),
                $propertyTransfer->getSingularAnnotationType(),
            ));

            return $propertyTransfer;
        }

        [$resolvedType, $isTransfer] = $this->resolveType($generatorConfigTransfer, $type);

        return $propertyTransfer
            ->setType($resolvedType)
            ->setIsTransfer($isTransfer)
            ->setSingularType(null)
            ->setIsSingularTransfer(false)
            ->setAnnotationType($this->getAnnotation($resolvedType, $isTransfer))
            ->setSingularAnnotationType(null);
    }

    /**
     * @param GeneratorConfigTransfer $generatorConfigTransfer
     * @param string $type
     *
     * @throws InvalidArgumentException
     *
     * @return array{string, bool} resolved type and whether it is a generated transfer
     */
    protected function resolveType(GeneratorConfigTransfer $generatorConfigTransfer, string $type): array
    {
        $type = ltrim($type, '\\');

        if (in_array($type, self::PHP_TYPES, true)) {
            return [$type, false];
        }

        if (isset($this->definedTransfers[$type])) {
            return [$generatorConfigTransfer->getNamespace() . '\\' . $type . 'Transfer', true];
        }

        if (class_exists($type) || interface_exists($type) || enum_exists($type)) {
            return [(new ReflectionClass($type))->getName(), false];
        }

        throw new InvalidArgumentException(sprintf(
            "Unknown type '%s': it is neither a PHP type, a defined transfer nor an existing class, interface or enum",
            $type,
        ));
    }

    /**
     * Transfers live in the generated namespace and are referenced by their short name,
     * classes are referenced fully qualified.
     *
     * @param string $type
     * @param bool $isTransfer
     *
     * @return string
     */
    protected function getAnnotation(string $type, bool $isTransfer): string
    {
        if (in_array($type, self::PHP_TYPES, true)) {
            return $type;
        }

        if ($isTransfer) {
            $parts = explode('\\', $type);

            return end($parts);
        }

        return '\\' . $type;
    }
}
