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

        if ($propertyTransfer->isCollection()) {
            $method->setReturnType($propertyTransfer->getType());
            $method->setComment('@return ' . $propertyTransfer->getAnnotationType());
            $method->addBody(sprintf(
                'return $this->%s ??= %s;',
                $propertyTransfer->getName(),
                $propertyTransfer->getType() === 'ArrayObject' ? 'new ArrayObject([])' : '[]',
            ));

            return;
        }

        $method->setReturnType(($propertyTransfer->isNullable() ? '?' :  '') . $propertyTransfer->getType());
        $method->setComment('@return ' . $propertyTransfer->getAnnotationType() . ($propertyTransfer->isNullable() ? '|null' : ''));
        $method->addBody('return $this->' . $propertyTransfer->getName() . ';');
    }
}
