<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;

class GetterGenerator implements GetterGeneratorInterface
{
    /**
     * @param GeneratorHelperInterface $generatorHelper
     */
    public function __construct(
        protected readonly GeneratorHelperInterface $generatorHelper,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function generateGetter(PropertyTransfer $property, TransferCollectionTransfer $transferCollection, string $code, ?string $overwriteMethodName = null): string
    {
        $propertyType = $this->generatorHelper->getPropertyType($property->getType(), $transferCollection);
        $annotationType = $this->generatorHelper->getPropertyAnnotationType($property->getType(), $propertyType);

        $code .= "    /**\n";
        $code .= sprintf(
            "     * @return %s\n",
            $property->getIsNullable() ? "$annotationType|null" : $annotationType,
        );
        $code .= "     */\n";
        $code .= sprintf(
            "    public function get%s(): %s%s\n",
            $overwriteMethodName ? ucfirst($overwriteMethodName) : ucfirst($property->getName()),
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
}