<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Service;

use PhilippHermes\TransferBundle\Transfer\TransferCollection;
use PhilippHermes\TransferBundle\Transfer\Transfer;

class TransferGenerator
{
    /**
     * @param string $namespace
     * @param string $outputDir
     */
    public function __construct(
        protected readonly string $namespace,
        protected readonly string $outputDir,
    ) {}

    public function generate(TransferCollection $collection): void
    {
        foreach ($collection->getTransfers() as $transfer) {
            $this->generateDto($transfer);
        }
    }

    private function generateDto(Transfer $transfer): void
    {
        $className = $transfer->getName();
        $namespace = $this->namespace;

        $code = "<?php\n\n";
        $code .= "declare(strict_types = 1);\n\n";
        $code .= "namespace $namespace;\n\n";

        $usesArrayObject = false;
        foreach ($transfer->getProperties() as $property) {
            if ($this->isObjectArrayType($property->getType())) {
                $usesArrayObject = true;
                break;
            }
        }
        if ($usesArrayObject) {
            $code .= "use ArrayObject;\n\n";
        }

        $code .= "class $className\n{\n";

        foreach ($transfer->getProperties() as $property) {
            $name = $property->getName();
            $type = $property->getType();
            $phpType = $this->getPhpType($type);
            $docType = $this->getDocType($type);
            $isNullable = $property->getIsNullable();

            $code .= "    /**\n";
            if ($property->getDescription()) {
                $code .= "     * " . $property->getDescription() . "\n";
                $code .= "     *\n";
            }
            $code .= "     * @var " . ($isNullable ? "$docType|null" : $docType) . "\n";
            $code .= "     */\n";
            $code .= "    protected " . ($isNullable ? '?' : '') . "$phpType \$$name;\n\n";
        }

        foreach ($transfer->getProperties() as $property) {
            $type = $property->getType();
            $phpType = $this->getPhpType($type);
            $docType = $this->getDocType($type);
            $isNullable = $property->getIsNullable();
            $propertyName = $property->getName();
            $elementType = rtrim($type, '[]');

            $singular = $property->getSingular() ?? $propertyName;
            $singularVar = lcfirst($singular);
            $methodName = ucfirst($propertyName);

            $code .= "    /**\n";
            $code .= "     * @return " . ($isNullable ? "$docType|null" : $docType) . "\n";
            $code .= "     */\n";
            $code .= "    public function get$methodName(): " . ($isNullable ? '?' : '') . "$phpType\n";
            $code .= "    {\n";
            $code .= "        return \$this->{$propertyName};\n";
            $code .= "    }\n\n";

            $code .= "    /**\n";
            $code .= "     * @param " . ($isNullable ? "$docType|null" : $docType) . " \$$propertyName\n";
            $code .= "     *\n";
            $code .= "     * @return self\n";
            $code .= "     */\n";
            $code .= "    public function set$methodName(" . ($isNullable ? '?' : '') . "$phpType \$$propertyName): self\n";
            $code .= "    {\n";
            $code .= "        \$this->{$propertyName} = \$$propertyName;\n\n";
            $code .= "        return \$this;\n";
            $code .= "    }\n\n";

            if ($this->isObjectArrayType($type)) {
                $code .= "    /**\n";
                $code .= "     * @param $elementType \$$singularVar\n";
                $code .= "     *\n";
                $code .= "     * @return self\n";
                $code .= "     */\n";
                $code .= "    public function add" . ucfirst($singular) . "($elementType \$$singularVar): self\n";
                $code .= "    {\n";
                $code .= "        if (!\$this->{$propertyName}) {\n";
                $code .= "            \$this->{$propertyName} = new ArrayObject();\n";
                $code .= "        }\n";
                $code .= "        \$this->{$propertyName}->append(\$$singularVar);\n\n";
                $code .= "        return \$this;\n";
                $code .= "    }\n\n";
            } else if ($this->isArrayType($type)) {
                $code .= "    /**\n";
                $code .= "     * @param $elementType \$$singularVar\n";
                $code .= "     *\n";
                $code .= "     * @return self\n";
                $code .= "     */\n";
                $code .= "    public function add" . ucfirst($singular) . "($elementType \$$singularVar): self\n";
                $code .= "    {\n";
                $code .= "        \$this->{$propertyName}[] = \$$singularVar;\n\n";
                $code .= "        return \$this;\n";
                $code .= "    }\n\n";
            }
        }

        $code .= "}\n";

        $filePath = $this->outputDir . "/$className.php";
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, $code);
    }

    private function isScalar(string $type): bool
    {
        return in_array(strtolower($type), ['int', 'string', 'bool', 'float', 'mixed']);
    }

    private function isArrayType(string $type): bool
    {
        return str_ends_with($type, '[]');
    }

    private function isObjectArrayType(string $type): bool
    {
        return $this->isArrayType($type) && !$this->isScalar(rtrim($type, '[]'));
    }

    private function getPhpType(string $type): string
    {
        if ($this->isArrayType($type)) {
            $elementType = rtrim($type, '[]');
            return $this->isScalar($elementType) ? 'array' : 'ArrayObject';
        }

        return $type;
    }

    private function getDocType(string $type): string
    {
        if ($this->isArrayType($type)) {
            $elementType = rtrim($type, '[]');
            return $this->isScalar($elementType)
                ? "array<array-key, " . strtolower($elementType) . ">"
                : "ArrayObject<array-key, {$elementType}>";
        }

        return $type;
    }
}
