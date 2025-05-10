<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
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
     * @inheritDoc
     */
    public function generateProperties(TransferTransfer $transferTransfer, TransferCollectionTransfer $transferCollection, string $code): string
    {
        foreach ($transferTransfer->getProperties() as $property) {
            $propertyType = $this->generatorHelper->getPropertyType($property->getType(), $transferCollection);
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

            $default = '';

            if ($property->getIsNullable()) {
                $default = ' = null';
            }

            if ($propertyType === 'array') {
                $default = ' = []';
            }

            $code .= "     */\n";
            $code .= sprintf(
                "    protected %s%s \$%s%s;\n\n",
                $property->getIsNullable() ? '?' : '',
                $propertyType,
                $property->getName(),
                $default,
            );
        }

        return $code;
    }
}