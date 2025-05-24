<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;

class SetterPropertyGeneratorStep implements PropertyGeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(PropertyTransfer $propertyTransfer, ClassType $class): void
    {
        $method = $class->addMethod('set' . ucfirst($propertyTransfer->getName()));

        $method->setPublic();
        $method->setReturnType('self');
        $method->setComment('@param ' . $propertyTransfer->getAnnotationType() . ' $' . $propertyTransfer->getName());
        $method->addBody('$this->' . $propertyTransfer->getName() . ' = $' . $propertyTransfer->getName() . ';');
        $method->addBody('return $this;');

        $methodParameter = $method->addParameter($propertyTransfer->getName());
        $methodParameter->setType(($propertyTransfer->isNullable() ? '?' :  '') . $propertyTransfer->getType());
    }
}