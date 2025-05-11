<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class PropertyGeneratorStep implements GeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer, TransferTransfer $transferTransfer, string $code): string
    {
        foreach ($transferTransfer->getProperties() as $propertyTransfer) {
            $code .= "    /**\n";

            if ($propertyTransfer->getDescription()) {
                $code .= sprintf("     * %s\n", $propertyTransfer->getDescription());
                $code .= "     *\n";
            }

            $annotationType = $propertyTransfer->getAnnotationType();
            $code .= sprintf(
                "     * @var %s\n",
                $propertyTransfer->getIsNullable() ? "$annotationType|null" : $annotationType,
            );

            $default = '';

            if ($propertyTransfer->getIsNullable()) {
                $default = ' = null';
            }

            if ($propertyTransfer->getRealType() === 'array') {
                $default = ' = []';
            }

            $code .= "     */\n";
            $code .= sprintf(
                "    protected %s%s \$%s%s;\n\n",
                $propertyTransfer->getIsNullable() ? '?' : '',
                $propertyTransfer->getRealType(),
                $propertyTransfer->getName(),
                $default,
            );
        }

        return $code;
    }
}