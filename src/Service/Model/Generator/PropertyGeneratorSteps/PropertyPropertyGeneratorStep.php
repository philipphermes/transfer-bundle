<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class PropertyPropertyGeneratorStep implements PropertyGeneratorStepInterface
{
    protected const array DATE_TIME_TYPES = ['DateTime', 'DateTimeImmutable', 'DateTimeInterface'];

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
        } elseif ($propertyTransfer->getType() === 'array') {
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
        $arguments = [];

        if ($propertyTransfer->isTransfer()) {
            $arguments['ref'] = $this->createModelReference($propertyTransfer->getType());
        } elseif ($propertyTransfer->getType() !== 'mixed') {
            $arguments['type'] = $this->resolveOAType($propertyTransfer->getType());
        }

        if ($propertyTransfer->getDescription() !== null) {
            $arguments['description'] = $propertyTransfer->getDescription();
        }

        $singularType = $propertyTransfer->getSingularType();

        if ($singularType !== null) {
            $items = [];

            if ($propertyTransfer->isSingularTransfer()) {
                $items['ref'] = $this->createModelReference($singularType);
            } elseif ($singularType !== 'mixed') {
                $items['type'] = $this->resolveOAType($singularType);
            }

            if (in_array($singularType, self::DATE_TIME_TYPES, true)) {
                $items['format'] = 'date-time';
            }

            $arguments['items'] = Literal::new('OA\Items', $items);
        }

        if (in_array($propertyTransfer->getType(), self::DATE_TIME_TYPES, true)) {
            $arguments['format'] = 'date-time';
        }

        if ($propertyTransfer->isNullable() && !$propertyTransfer->isCollection()) {
            $arguments['nullable'] = true;
        }

        return $arguments;
    }

    /**
     * @param string $type
     *
     * @return Literal
     */
    protected function createModelReference(string $type): Literal
    {
        return Literal::new('\Nelmio\ApiDocBundle\Attribute\Model', ['type' => $type]);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function resolveOAType(string $type): string
    {
        return match ($type) {
            'string', 'DateTime', 'DateTimeImmutable', 'DateTimeInterface' => 'string',
            'int' => 'integer',
            'float' => 'number',
            'bool' => 'boolean',
            'array', 'ArrayObject' => 'array',
            default => 'object',
        };
    }
}
