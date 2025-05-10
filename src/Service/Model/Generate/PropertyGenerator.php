<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class PropertyGenerator implements PropertyGeneratorInterface
{
    /**
     * @param GeneratorHelperInterface $generatorHelper
     */
    public function __construct(
        protected readonly GeneratorHelperInterface $generatorHelper,
    ) {
    }

    /**
     * @param TransferTransfer $transferTransfer
     * @param string $code
     *
     * @return string
     */
    public function generateProperties(TransferTransfer $transferTransfer, string $code): string
    {
        foreach ($transferTransfer->getProperties() as $property) {
            $propertyType = $this->generatorHelper->getPropertyType($property->getType());
            $annotationType = $this->generatorHelper->getPropertyAnnotationType($property->getType(), $propertyType);

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
}