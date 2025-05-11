<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

interface GeneratorStepInterface
{
    /**
     * @param GeneratorConfigTransfer $generatorConfigTransfer
     * @param TransferTransfer $transferTransfer
     * @param string $code
     *
     * @return string
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer, TransferTransfer $transferTransfer, string $code): string;
}