<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class HasPropertyGeneratorStep implements PropertyGeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(TransferTransfer $transferTransfer, PropertyTransfer $propertyTransfer, ClassType $class): void
    {
        $method = $class->addMethod('has' . ucfirst($propertyTransfer->getName()));
        $method->setPublic();
        $method->setReturnType('bool');
        $method->setComment('@return bool whether the property is set and not null');

        if ($propertyTransfer->isDeprecated()) {
            $method->addComment('@deprecated');
        }
        $method->addBody($propertyTransfer->isAlwaysInitialized()
            ? 'return true;'
            : 'return isset($this->' . $propertyTransfer->getName() . ');');
    }
}
