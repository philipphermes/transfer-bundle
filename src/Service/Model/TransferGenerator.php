<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Service\Model;

use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

readonly class TransferGenerator implements TransferGeneratorInterface
{
    /**
     * @param string $namespace
     * @param string $outputDir
     */
    public function __construct(protected string $namespace, protected string $outputDir) {}

    /**
     * @inheritDoc
     */
    public function generateTransfer(TransferTransfer $transfer): void
    {
        $code = $this->generateClassHeader($transfer);
        $code = $this->generateProperties($transfer, $code);
        $code = $this->generateGettersSettersAndAdders($transfer, $code);

        $code .= "}\n";

        $filePath = $this->outputDir . "/" . $transfer->getName() . "Transfer.php";
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, $code);
    }

    /**
     * @param TransferTransfer $transferTransfer
     *
     * @return string
     */
    protected function generateClassHeader(TransferTransfer $transferTransfer): string
    {
        $code = "<?php\n\n";
        $code .= "declare(strict_types = 1);\n\n";
        $code .= sprintf("namespace %s;\n\n", $this->namespace);

        $useTypes = [];

        foreach ($transferTransfer->getProperties() as $property) {
            $propertyType = $this->getPropertyType($property->getType());

            if (!$this->isBasicType($propertyType) && $propertyType !== 'array' && !in_array($propertyType, $useTypes, true)) {
                $code .= sprintf("use %s;\n", $propertyType);
                $useTypes[] = $propertyType;
            }
        }

        if ($useTypes) {
            $code .= "\n";
        }

        $code .= sprintf("class %sTransfer\n{\n", $transferTransfer->getName());

        return $code;
    }

    /**
     * @param TransferTransfer $transferTransfer
     * @param string $code
     *
     * @return string
     */
    protected function generateProperties(TransferTransfer $transferTransfer, string $code): string
    {
        foreach ($transferTransfer->getProperties() as $property) {
            $propertyType = $this->getPropertyType($property->getType());
            $annotationType = $this->getPropertyAnnotationType($property->getType(), $propertyType);

            $code .= "    /**\n";

            if ($property->getDescription()) {
                $code .= sprintf("     * %s\n", $property->getDescription());
                $code .= "     *\n";
            }

            $code .= sprintf(
                "     * @var %s\n",
                $property->getIsNullable() ? "$annotationType|null" : $annotationType,
            );

            $code .= "     */\n";
            $code .= sprintf(
                "    protected %s%s \$%s;\n\n",
                $property->getIsNullable() ? '?' : '',
                $propertyType,
                $property->getName(),
            );
        }

        return $code;
    }

    /**
     * @param TransferTransfer $transferTransfer
     * @param string $code
     *
     * @return string
     */
    protected function generateGettersSettersAndAdders(TransferTransfer $transferTransfer, string $code): string
    {
        foreach ($transferTransfer->getProperties() as $property) {
            $code = $this->generateGetter($property, $code);
            $code = $this->generateSetter($property, $code);
            $code = $this->generateAdder($property, $code);
        }

        return $code;
    }

    /**
     * @param PropertyTransfer $property
     * @param string $code
     *
     * @return string
     */
    protected function generateGetter(PropertyTransfer $property, string $code): string
    {
        $propertyType = $this->getPropertyType($property->getType());
        $annotationType = $this->getPropertyAnnotationType($property->getType(), $propertyType);

        $code .= "    /**\n";
        $code .= sprintf(
            "     * @return %s\n",
            $property->getIsNullable() ? "$annotationType|null" : $annotationType,
        );
        $code .= "     */\n";
        $code .= sprintf(
            "    public function get%s(): %s%s\n",
            ucfirst($property->getName()),
            $property->getIsNullable() ? '?' : '',
            $propertyType,
        );
        $code .= "    {\n";

        if ($propertyType === 'ArrayObject') {
            $code .= sprintf(
                "        if (!isset(\$this->%s)) {\n",
                $property->getName(),
            );
            $code .= sprintf(
                "            \$this->%s = new ArrayObject();\n",
                $property->getName(),
            );
            $code .= "        }\n";
        }

        $code .= sprintf(
            "        return \$this->%s;\n",
            $property->getName(),
        );
        $code .= "    }\n\n";

        return $code;
    }

    /**
     * @param PropertyTransfer $property
     * @param string $code
     *
     * @return string
     */
    protected function generateSetter(PropertyTransfer $property, string $code): string
    {
        $propertyType = $this->getPropertyType($property->getType());
        $annotationType = $this->getPropertyAnnotationType($property->getType(), $propertyType);

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
    protected function generateAdder(PropertyTransfer $property, string $code): string
    {
        $propertyType = $this->getPropertyType($property->getType());

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

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getPropertyType(string $type): string
    {
        if ($this->isArrayType($type)) {
            $propertyType = rtrim($type, '[]');

            return $this->isBasicType($propertyType) ? 'array' : 'ArrayObject';
        }

        return $this->isBasicType($type) ? strtolower($type) : $type;
    }

    /**
     * @param string $type
     * @param string $propertyType
     *
     * @return string
     */
    protected function getPropertyAnnotationType(string $type, string $propertyType): string
    {
        if ($propertyType === 'array' || $propertyType === 'ArrayObject') {
            return sprintf(
                '%s<array-key, %s>',
                $propertyType,
                $propertyType === 'ArrayObject' ? rtrim($type, '[]') . 'Transfer' : strtolower($type === 'array' ? 'mixed' : rtrim($type, '[]'))
            );
        }

        return $propertyType;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    protected function isBasicType(string $type): bool
    {
        return in_array(strtolower($type), ['int', 'string', 'bool', 'float', 'mixed', 'object'], true);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    protected function isArrayType(string $type): bool
    {
        return str_ends_with($type, '[]');
    }
}
