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
        $propertyName = lcfirst($transfer->getSingular() ?? $transfer->getName());

        $type = $transfer->getType();
        $isNullable = $transfer->getIsNullable();

        $phpType = $this->getPhpType($type);
        $docType = $this->getDocType($type);

        $code = "<?php\n\n";
        $code .= "namespace $namespace;\n\n";
        if (str_contains($docType, 'ArrayObject')) {
            $code .= "use ArrayObject;\n\n";
        }

        $code .= "/**\n";
        if ($desc = $transfer->getDescription()) {
            $code .= " * {$desc}\n";
        }
        $code .= " */\n";
        $code .= "class $className\n{\n";

        // PHPDoc and property
        $code .= "    /**\n";
        $code .= "     * @var $docType\n";
        $code .= "     */\n";
        $code .= "    public " . ($isNullable ? '?' : '') . "$phpType \$$propertyName;\n\n";

        // Add method for object arrays only
        if ($this->isObjectArrayType($type)) {
            $elementType = rtrim($type, '[]');
            $singular = $transfer->getSingular() ?? $elementType;
            $singularVar = lcfirst($singular);

            $code .= "    public function add" . ucfirst($singular) . "($elementType \$$singularVar): void\n";
            $code .= "    {\n";
            $code .= "        if (!\$this->{$propertyName}) {\n";
            $code .= "            \$this->{$propertyName} = new ArrayObject();\n";
            $code .= "        }\n";
            $code .= "        \$this->{$propertyName}->append(\$$singularVar);\n";
            $code .= "    }\n";
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
        return in_array(strtolower($type), ['int', 'integer', 'string', 'bool', 'boolean', 'float', 'double', 'mixed']);
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

        return match (strtolower($type)) {
            'int', 'integer' => 'int',
            'string' => 'string',
            'bool', 'boolean' => 'bool',
            'float', 'double' => 'float',
            'mixed' => 'mixed',
            default => $type, // assume DTO
        };
    }

    private function getDocType(string $type): string
    {
        if ($this->isArrayType($type)) {
            $elementType = rtrim($type, '[]');
            return $this->isScalar($elementType)
                ? "array<array-key, {$this->normalizeScalar($elementType)}>"
                : "ArrayObject<array-key, {$elementType}>";
        }

        return match (strtolower($type)) {
            'int', 'integer' => 'int',
            'string' => 'string',
            'bool', 'boolean' => 'bool',
            'float', 'double' => 'float',
            'mixed' => 'mixed',
            default => $type,
        };
    }

    private function normalizeScalar(string $type): string
    {
        return match (strtolower($type)) {
            'int', 'integer' => 'int',
            'bool', 'boolean' => 'bool',
            'float', 'double' => 'float',
            default => strtolower($type),
        };
    }
}
