<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;

class AdderPropertyGeneratorStep implements PropertyGeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(PropertyTransfer $propertyTransfer, ClassType $class): void
    {
        if (!$propertyTransfer->getSingularType()) {
            return;
        }

        $method = $class->addMethod('add' . ucfirst($propertyTransfer->getSingular() ?? $propertyTransfer->getName()));
        $method->setPublic();
        $method->setReturnType('self');
        $method->setComment('@param ' . $propertyTransfer->getSingularAnnotationType() . ' $' . ($propertyTransfer->getSingular() ?? $propertyTransfer->getName()));

        if ($propertyTransfer->getType() === 'ArrayObject') {
            $method->addBody('if (!isset($this->' . $propertyTransfer->getName() . ')) $this->' . $propertyTransfer->getName() . ' = new ArrayObject([]);');
            $method->addBody('$this->' . $propertyTransfer->getName() . '->append($' . ($propertyTransfer->getSingular() ?? $propertyTransfer->getName()) . ');');
        } else {
            $method->addBody('$this->' . $propertyTransfer->getName() . '[] = $' . ($propertyTransfer->getSingular() ?? $propertyTransfer->getName()) . ';');
        }

        $method->addBody('return $this;');

        $methodProperty = $method->addParameter($propertyTransfer->getSingular() ?? $propertyTransfer->getName());
        $methodProperty->setType(($propertyTransfer->isNullable() ? '?' :  '') . $propertyTransfer->getSingularType());
    }
}