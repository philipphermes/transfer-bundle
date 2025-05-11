<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class SensitiveGeneratorStep implements GeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer, TransferTransfer $transferTransfer, string $code): string
    {
        if ($transferTransfer->getSensitiveProperties()->count() === 0) {
            return $code;
        }

        $code .= "    /**\n";
        $code .= "     * @return void\n";
        $code .= "     */\n";
        $code .= "    public function eraseCredentials(): void\n";
        $code .= "    {\n";

        foreach ($transferTransfer->getSensitiveProperties() as $propertyTransfer) {
            $code .= sprintf(
                "        \$this->%s = null;\n",
                $propertyTransfer->getName(),
            );
        }

        $code .= "    }\n\n";

        return $code;
    }
}