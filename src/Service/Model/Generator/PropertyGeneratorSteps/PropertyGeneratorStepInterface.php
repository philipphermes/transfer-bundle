<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;

interface PropertyGeneratorStepInterface
{
    /**
     * @param PropertyTransfer $propertyTransfer
     * @param ClassType $class
     * @return void
     */
    public function generate(PropertyTransfer $propertyTransfer, ClassType $class): void;
}