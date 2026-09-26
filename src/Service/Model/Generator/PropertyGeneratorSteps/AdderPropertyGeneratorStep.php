<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class AdderPropertyGeneratorStep implements PropertyGeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(TransferTransfer $transferTransfer, PropertyTransfer $propertyTransfer, ClassType $class): void
    {
        $singularType = $propertyTransfer->getSingularType();

        if ($singularType === null) {
            return;
        }

        $name = $propertyTransfer->getName();
        $parameterName = $propertyTransfer->getSingular() ?? $name;

        $method = $class->addMethod('add' . ucfirst($parameterName));
        $method->setPublic();
        $method->setReturnType('self');
        $method->setComment('@param ' . $propertyTransfer->getSingularAnnotationType() . ' $' . $parameterName);

        if ($propertyTransfer->isDeprecated()) {
            $method->addComment('@deprecated');
        }

        if ($propertyTransfer->getType() === 'ArrayObject') {
            $method->addBody('($this->' . $name . ' ??= new ArrayObject([]))->append($' . $parameterName . ');');
        } else {
            $method->addBody('$this->' . $name . '[] = $' . $parameterName . ';');
        }

        $method->addBody('return $this;');

        $method->addParameter($parameterName)->setType($singularType);
    }
}
