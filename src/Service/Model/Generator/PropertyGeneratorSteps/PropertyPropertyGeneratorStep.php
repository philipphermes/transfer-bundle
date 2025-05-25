<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class PropertyPropertyGeneratorStep implements PropertyGeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(TransferTransfer $transferTransfer, PropertyTransfer $propertyTransfer, ClassType $class): void
    {
        $property = $class->addProperty($propertyTransfer->getName());
        $property->setPrivate();
        $property->setType(($propertyTransfer->isNullable() ? '?' : '') . $propertyTransfer->getType());
        $property->addComment('@var ' . $propertyTransfer->getAnnotationType() . ($propertyTransfer->isNullable() ? '|null' : ''));

        if ($transferTransfer->isApi()) {
            $property->addComment($this->resolveOAAnnotation($propertyTransfer));
        }

        if ($propertyTransfer->isNullable()) {
            $property->setValue(null);
        }

        if ($propertyTransfer->getType() === 'array') {
            $property->setValue([]);
        }
    }

    protected function resolveOAAnnotation(PropertyTransfer $propertyTransfer): string
    {
        $type = $this->resolveOAType($propertyTransfer->getType());
        $singularType = $propertyTransfer->getSingularType() ? $this->resolveOAType($propertyTransfer->getSingularType()) : null;

        $annotation = '';

        if (str_contains($propertyTransfer->getType(), 'Transfer')) {
            $annotation = sprintf(
                'ref="#/components/schemas/%s"',
                $this->resolveTransferType($propertyTransfer->getType()),
            );
        } else {
            $annotation = sprintf('type="%s"', $type);
        }

        if ($singularType) {
            $annotation = sprintf(
                '@OA\Items(%s)',
                str_contains($propertyTransfer->getSingularType(), 'Transfer')
                ? 'ref="#/components/schemas/' . $this->resolveTransferType($propertyTransfer->getSingularType()) . '"'
                : 'property="' . $singularType . '"',
            );
        }

        if ($propertyTransfer->getType() === 'DateTime' || $propertyTransfer->getType() === 'DateTimeImmutable') {
            $annotation .= ', format="date-time"';
        }

        if ($propertyTransfer->isNullable()) {
            $annotation .= ', nullable="true"';
        }

        return sprintf(
            '@OA\Property(%s)',
            $annotation,
        );
    }

    /**
     * @param PropertyTransfer $propertyTransfer
     *
     * @return string
     */
    protected function resolveOAType(string $type): string
    {
        return match ($type) {
            'string', 'DateTime', 'DateTimeImmutable' => 'string',
            'int' => 'integer',
            'float' => 'number',
            'bool' => 'boolean',
            'array', 'ArrayObject' => 'array',
            default => 'object',
        };
    }

    /**
     * @param string $type
     *
     * @return string
     */
    function resolveTransferType(string $type): string
    {
        $parts = explode('\\', $type);
        $shortType = end($parts);

        return str_replace('Transfer', '', $shortType);
    }
}