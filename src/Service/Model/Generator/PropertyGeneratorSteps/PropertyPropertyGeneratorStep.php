<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use OpenApi\Attributes\Items;
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
            $property->addAttribute(
                'OpenApi\Attributes\Property',
                $this->resolveOAAttributeArguments($propertyTransfer),
            );
        }

        if ($propertyTransfer->isNullable()) {
            $property->setValue(null);
        }

        if ($propertyTransfer->getType() === 'array') {
            $property->setValue([]);
        }
    }

    /**
     * @param PropertyTransfer $propertyTransfer
     *
     * @return array<string, mixed>
     */
    protected function resolveOAAttributeArguments(PropertyTransfer $propertyTransfer): array
    {
        $type = $this->resolveOAType($propertyTransfer->getType());
        $singularType = $propertyTransfer->getSingularType() ? $this->resolveOAType($propertyTransfer->getSingularType()) : null;

        $arguments = [];

        if (str_contains($propertyTransfer->getType(), 'Transfer')) {
            $arguments['ref'] = sprintf(
                    '#/components/schemas/%s',
                    $this->resolveTransferType($propertyTransfer->getType()),
            );
        } else {
            $arguments['type'] = $type;
        }

        if ($singularType) {
            if (str_contains($propertyTransfer->getSingularType(), 'Transfer')) {
                $arguments['items'] = new Literal(
                    'OpenApi\Attributes\Items',
                    [
                        'ref' => sprintf(
                            '#/components/schemas/%s',
                            $this->resolveTransferType($propertyTransfer->getSingularType()),
                        ),
                    ],
                );
            } else {
                $arguments['items'] = new Literal(
                    'OpenApi\Attributes\Items',
                    [
                        'type' => $singularType,
                    ],
                );
            }
        }

        if ($propertyTransfer->getType() === 'DateTime' || $propertyTransfer->getType() === 'DateTimeImmutable') {
            $arguments['format'] = 'date-time';
        }

        if ($propertyTransfer->isNullable()) {
            $arguments['nullable'] = true;
        }

        return $arguments;
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