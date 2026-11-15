<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use BackedEnum;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;
use ReflectionEnum;
use ReflectionNamedType;
use UnitEnum;

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

        if ($propertyTransfer->isDeprecated()) {
            $property->addComment('@deprecated');
        }

        if ($transferTransfer->isApi()) {
            $property->addAttribute(
                'OpenApi\Attributes\Property',
                $this->resolveOAAttributeArguments($propertyTransfer),
            );
        }

        if ($propertyTransfer->hasDefaultValue()) {
            $property->setValue($propertyTransfer->getDefaultValue());
        } elseif ($propertyTransfer->isNullable()) {
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
            $arguments += $this->resolveOATypeArguments($propertyTransfer->getType());
        }

        if ($propertyTransfer->getDescription() !== null) {
            $arguments['description'] = $propertyTransfer->getDescription();
        }

        if ($propertyTransfer->hasDefaultValue()) {
            $arguments['default'] = $propertyTransfer->getDefaultValue();
        }

        if ($propertyTransfer->getExample() !== null) {
            $arguments['example'] = $propertyTransfer->getExample();
        }

        $singularType = $propertyTransfer->getSingularType();

        if ($singularType !== null) {
            $items = [];

            if ($propertyTransfer->isSingularTransfer()) {
                $items['ref'] = $this->createModelReference($singularType);
            } elseif ($singularType !== 'mixed') {
                $items += $this->resolveOATypeArguments($singularType);
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

        if ($propertyTransfer->isDeprecated()) {
            $arguments['deprecated'] = true;
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
     * Backed enums are documented with their backing type and cases.
     *
     * @param string $type
     *
     * @return array<string, mixed>
     */
    protected function resolveOATypeArguments(string $type): array
    {
        if (is_subclass_of($type, BackedEnum::class)) {
            $backingType = (new ReflectionEnum($type))->getBackingType();

            return [
                'type' => $this->resolveOAType($backingType instanceof ReflectionNamedType ? $backingType->getName() : 'string'),
                'enum' => array_map(fn (BackedEnum $case): int|string => $case->value, $type::cases()),
            ];
        }

        if (is_subclass_of($type, UnitEnum::class)) {
            return [
                'type' => 'string',
                'enum' => array_map(fn (UnitEnum $case): string => $case->name, $type::cases()),
            ];
        }

        return ['type' => $this->resolveOAType($type)];
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
