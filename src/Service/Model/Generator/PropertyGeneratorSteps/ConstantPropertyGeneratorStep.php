<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

/**
 * Adds a public constant holding the property name, e.g. `public const string EMAIL = 'email';`, used as array key by toArray()/fromArray().
 */
class ConstantPropertyGeneratorStep implements PropertyGeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(TransferTransfer $transferTransfer, PropertyTransfer $propertyTransfer, ClassType $class): void
    {
        $class->addConstant($propertyTransfer->getConstantName(), $propertyTransfer->getName())
            ->setPublic()
            ->setType('string');
    }
}
