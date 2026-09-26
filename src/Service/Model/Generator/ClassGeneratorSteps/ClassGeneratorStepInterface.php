<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\ClassGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

interface ClassGeneratorStepInterface
{
    /**
     * Runs once per transfer, after all property steps.
     *
     * @param TransferTransfer $transferTransfer
     * @param ClassType $class
     *
     * @return void
     */
    public function generate(TransferTransfer $transferTransfer, ClassType $class): void;
}
