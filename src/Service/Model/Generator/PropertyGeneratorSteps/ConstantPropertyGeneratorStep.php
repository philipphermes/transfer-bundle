<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class ConstantPropertyGeneratorStep implements PropertyGeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(TransferTransfer $transferTransfer, PropertyTransfer $propertyTransfer, ClassType $class): void
    {
        $constantName = $this->toConstantName($propertyTransfer->getName());
        $constant = $class->addConstant($constantName, $propertyTransfer->getName());
        $constant->setPublic();
    }

    /**
     * @param string $propertyName
     *
     * @return string
     */
    protected function toConstantName(string $propertyName): string
    {
        return strtoupper(preg_replace('/(?<!^)[A-Z]/', '_$0', $propertyName) ?? $propertyName);
    }
}
