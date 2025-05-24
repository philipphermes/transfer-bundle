<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;

class PropertyPropertyGeneratorStep implements PropertyGeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(PropertyTransfer $propertyTransfer, ClassType $class): void
    {
        $property = $class->addProperty($propertyTransfer->getName());
        $property->setPrivate();
        $property->setType(($propertyTransfer->isNullable() ? '?' : '') . $propertyTransfer->getType());
        $property->addComment('@var ' . $propertyTransfer->getAnnotationType() . ($propertyTransfer->isNullable() ? '|null' : ''));

        if ($propertyTransfer->isNullable()) {
            $property->setValue(null);
        }

        if ($propertyTransfer->getType() === 'array') {
            $property->setValue([]);
        }
    }
}