<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class GetterPropertyGeneratorStep implements PropertyGeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(TransferTransfer $transferTransfer, PropertyTransfer $propertyTransfer, ClassType $class): void
    {
        $method = $class->addMethod('get' . ucfirst($propertyTransfer->getName()));
        $method->setPublic();
        $method->setReturnType(($propertyTransfer->isNullable() ? '?' :  '') . $propertyTransfer->getType());
        $method->setComment('@return ' . $propertyTransfer->getAnnotationType() . ($propertyTransfer->isNullable() ? '|null' : ''));

        if ($propertyTransfer->getType() === 'ArrayObject') {
            $method->addBody('if (!isset($this->' . $propertyTransfer->getName() . ')) $this->' . $propertyTransfer->getName() . ' = new ArrayObject([]);');
        }

        $method->addBody('return $this->' . $propertyTransfer->getName() . ';');
    }
}