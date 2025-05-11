<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class ClassEndGeneratorStep implements GeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer, TransferTransfer $transferTransfer, string $code): string
    {
        $code .= "}\n";

        return $code;
    }
}