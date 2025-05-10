<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;

class SetterGenerator implements SetterGeneratorInterface
{
    /**
     * @param GeneratorHelperInterface $generatorHelper
     */
    public function __construct(
        protected readonly GeneratorHelperInterface $generatorHelper,
    ) {
    }

    /**
     * @param PropertyTransfer $property
     * @param string $code
     *
     * @return string
     */
    public function generateSetter(PropertyTransfer $property, string $code): string
    {
        $propertyType = $this->generatorHelper->getPropertyType($property->getType());
        $annotationType = $this->generatorHelper->getPropertyAnnotationType($property->getType(), $propertyType);

        $code .= "    /**\n";
        $code .= sprintf(
            "     * @param %s \$%s\n",
            $property->getIsNullable() ? "$annotationType|null" : $annotationType,
            $property->getName(),
        );
        $code .= "     *\n";
        $code .= "     * @return self\n";
        $code .= "     */\n";
        $code .= sprintf(
            "    public function set%s(%s%s \$%s): self\n",
            ucfirst($property->getName()),
            $property->getIsNullable() ? '?' : '',
            $propertyType,
            $property->getName(),
        );
        $code .= "    {\n";
        $code .= sprintf(
            "        \$this->%s = \$%s;\n\n",
            $property->getName(),
            $property->getName(),
        );
        $code .= "        return \$this;\n";
        $code .= "    }\n\n";

        return $code;
    }

    /**
     * @param PropertyTransfer $property
     * @param string $code
     *
     * @return string
     */
    public function generateAdder(PropertyTransfer $property, string $code): string
    {
        $propertyType = $this->generatorHelper->getPropertyType($property->getType());

        if ($propertyType === 'ArrayObject') {
            $code .= "    /**\n";
            $code .= sprintf(
                "     * @param %sTransfer \$%s\n",
                rtrim($property->getType(), '[]'),
                $property->getSingular() ?? $property->getName(),
            );
            $code .= "     *\n";
            $code .= "     * @return self\n";
            $code .= "     */\n";
            $code .= sprintf(
                "    public function add%s(%sTransfer \$%s): self\n",
                ucfirst($property->getSingular() ?? $property->getName()),
                rtrim($property->getType(), '[]'),
                $property->getSingular() ?? $property->getName(),
            );
            $code .= "    {\n";
            $code .= sprintf(
                "        if (!isset(\$this->%s)) {\n",
                $property->getName(),
            );
            $code .= sprintf(
                "            \$this->%s = new ArrayObject();\n",
                $property->getName(),
            );
            $code .= "        }\n\n";
            $code .= sprintf(
                "        \$this->%s->append(\$%s);\n\n",
                $property->getName(),
                $property->getSingular() ?? $property->getName(),
            );
            $code .= "        return \$this;\n";
            $code .= "    }\n\n";

            return $code;
        }

        if ($propertyType === 'array') {
            $code .= "    /**\n";
            $code .= sprintf(
                "     * @param %s \$%s\n",
                rtrim($property->getType(), '[]'),
                $property->getSingular() ?? $property->getName(),
            );
            $code .= "     *\n";
            $code .= "     * @return self\n";
            $code .= "     */\n";
            $code .= sprintf(
                "    public function add%s(%s \$%s): self\n",
                ucfirst($property->getSingular() ?? $property->getName()),
                rtrim($property->getType(), '[]'),
                $property->getSingular() ?? $property->getName(),
            );
            $code .= "    {\n";
            $code .= sprintf(
                "        \$this->%s[] = \$%s;\n\n",
                $property->getName(),
                $property->getSingular() ?? $property->getName(),
            );
            $code .= "        return \$this;\n";
            $code .= "    }\n\n";
        }

        return $code;
    }
}