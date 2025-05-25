<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

interface PropertyGeneratorStepInterface
{
    /**
     * @param TransferTransfer $transferTransfer
     * @param PropertyTransfer $propertyTransfer
     * @param ClassType $class
     *
     * @return void
     */
    public function generate(TransferTransfer $transferTransfer, PropertyTransfer $propertyTransfer, ClassType $class): void;
}