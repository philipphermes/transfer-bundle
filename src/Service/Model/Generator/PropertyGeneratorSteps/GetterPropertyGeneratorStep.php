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

        if ($propertyTransfer->isDeprecated()) {
            $method->addComment('@deprecated');
        }

        if ($propertyTransfer->isCollection()) {
            $method->setReturnType($propertyTransfer->getType());
            $method->addComment('@return ' . $propertyTransfer->getAnnotationType());

            if ($propertyTransfer->isAlwaysInitialized()) {
                $method->addBody('return $this->' . $propertyTransfer->getName() . ';');

                return;
            }

            $method->addBody(sprintf(
                'return $this->%s ??= %s;',
                $propertyTransfer->getName(),
                $propertyTransfer->getType() === 'ArrayObject' ? 'new ArrayObject([])' : '[]',
            ));

            return;
        }

        $method->setReturnType(($propertyTransfer->isNullable() ? '?' :  '') . $propertyTransfer->getType());
        $method->addComment('@return ' . $propertyTransfer->getAnnotationType() . ($propertyTransfer->isNullable() ? '|null' : ''));
        $method->addBody('return $this->' . $propertyTransfer->getName() . ';');
    }
}
