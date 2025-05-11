<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class GetterGeneratorStep implements GeneratorStepInterface
{
    protected bool $generatedUserIdentifier;

    /**
     * @inheritDoc
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer, TransferTransfer $transferTransfer, string $code): string
    {
        $this->generatedUserIdentifier = false;

        foreach ($transferTransfer->getProperties() as $propertyTransfer) {
            $code = $this->generateGetter($propertyTransfer, $code);

            if ($propertyTransfer->getIsIdentifier()) {
                $code = $this->generateGetter($propertyTransfer, $code, 'userIdentifier');

                $this->generatedUserIdentifier = true;
            }
        }

        return $code;
    }

    /**
     * @param PropertyTransfer $propertyTransfer
     * @param string $code
     * @param string|null $overwriteMethodName
     *
     * @return string
     */
    protected function generateGetter(PropertyTransfer $propertyTransfer, string $code, ?string $overwriteMethodName = null): string
    {
        $annotationType = $propertyTransfer->getAnnotationType();

        $code .= "    /**\n";
        $code .= sprintf(
            "     * @return %s\n",
            $propertyTransfer->getIsNullable() ? "$annotationType|null" : $annotationType,
        );
        $code .= "     */\n";
        $code .= sprintf(
            "    public function get%s(): %s%s\n",
            $overwriteMethodName ? ucfirst($overwriteMethodName) : ucfirst($propertyTransfer->getName()),
            $propertyTransfer->getIsNullable() ? '?' : '',
            $propertyTransfer->getRealType(),
        );
        $code .= "    {\n";

        if ($propertyTransfer->getRealType() === 'ArrayObject') {
            $code .= sprintf(
                "        if (!isset(\$this->%s)) {\n",
                $propertyTransfer->getName(),
            );
            $code .= sprintf(
                "            \$this->%s = new ArrayObject();\n",
                $propertyTransfer->getName(),
            );
            $code .= "        }\n";
        }

        $code .= sprintf(
            "        return \$this->%s;\n",
            $propertyTransfer->getName(),
        );
        $code .= "    }\n\n";

        return $code;
    }
}