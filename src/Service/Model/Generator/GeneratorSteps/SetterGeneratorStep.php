<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class SetterGeneratorStep implements GeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer, TransferTransfer $transferTransfer, string $code): string
    {
        foreach ($transferTransfer->getProperties() as $property) {
            $code = $this->generateSetter($property, $code);
            $code = $this->generateAdder($property, $code);
        }

        return $code;
    }

    /**
     * @param PropertyTransfer $propertyTransfer
     * @param string $code
     *
     * @return string
     */
    protected function generateSetter(PropertyTransfer $propertyTransfer, string $code): string
    {
        $code .= "    /**\n";

        $annotationType = $propertyTransfer->getAnnotationType();
        $code .= sprintf(
            "     * @param %s \$%s\n",
            $propertyTransfer->getIsNullable() ? "$annotationType|null" : $annotationType,
            $propertyTransfer->getName(),
        );
        $code .= "     *\n";
        $code .= "     * @return self\n";
        $code .= "     */\n";
        $code .= sprintf(
            "    public function set%s(%s%s \$%s): self\n",
            ucfirst($propertyTransfer->getName()),
            $propertyTransfer->getIsNullable() ? '?' : '',
            $propertyTransfer->getRealType(),
            $propertyTransfer->getName(),
        );
        $code .= "    {\n";
        $code .= sprintf(
            "        \$this->%s = \$%s;\n\n",
            $propertyTransfer->getName(),
            $propertyTransfer->getName(),
        );
        $code .= "        return \$this;\n";
        $code .= "    }\n\n";

        return $code;
    }

    /**
     * @param PropertyTransfer $propertyTransfer
     * @param string $code
     *
     * @return string
     */
    protected function generateAdder(PropertyTransfer $propertyTransfer, string $code): string
    {
        if ($propertyTransfer->getRealType() === 'ArrayObject') {
            $code .= "    /**\n";
            $code .= sprintf(
                "     * @param %sTransfer \$%s\n",
                rtrim($propertyTransfer->getType(), '[]'),
                $propertyTransfer->getSingular() ?? $propertyTransfer->getName(),
            );
            $code .= "     *\n";
            $code .= "     * @return self\n";
            $code .= "     */\n";
            $code .= sprintf(
                "    public function add%s(%sTransfer \$%s): self\n",
                ucfirst($propertyTransfer->getSingular() ?? $propertyTransfer->getName()),
                rtrim($propertyTransfer->getType(), '[]'),
                $propertyTransfer->getSingular() ?? $propertyTransfer->getName(),
            );
            $code .= "    {\n";
            $code .= sprintf(
                "        if (!isset(\$this->%s)) {\n",
                $propertyTransfer->getName(),
            );
            $code .= sprintf(
                "            \$this->%s = new ArrayObject();\n",
                $propertyTransfer->getName(),
            );
            $code .= "        }\n\n";
            $code .= sprintf(
                "        \$this->%s->append(\$%s);\n\n",
                $propertyTransfer->getName(),
                $propertyTransfer->getSingular() ?? $propertyTransfer->getName(),
            );
            $code .= "        return \$this;\n";
            $code .= "    }\n\n";

            return $code;
        }

        if ($propertyTransfer->getRealType() === 'array') {
            $code .= "    /**\n";
            $code .= sprintf(
                "     * @param %s \$%s\n",
                rtrim($propertyTransfer->getType(), '[]'),
                $propertyTransfer->getSingular() ?? $propertyTransfer->getName(),
            );
            $code .= "     *\n";
            $code .= "     * @return self\n";
            $code .= "     */\n";
            $code .= sprintf(
                "    public function add%s(%s \$%s): self\n",
                ucfirst($propertyTransfer->getSingular() ?? $propertyTransfer->getName()),
                rtrim($propertyTransfer->getType(), '[]'),
                $propertyTransfer->getSingular() ?? $propertyTransfer->getName(),
            );
            $code .= "    {\n";
            $code .= sprintf(
                "        \$this->%s[] = \$%s;\n\n",
                $propertyTransfer->getName(),
                $propertyTransfer->getSingular() ?? $propertyTransfer->getName(),
            );
            $code .= "        return \$this;\n";
            $code .= "    }\n\n";
        }

        return $code;
    }
}