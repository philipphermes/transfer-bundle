<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class ClassGenerator implements ClassGeneratorInterface
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
     * @param string $namespace
     *
     * @return string
     */
    public function generateClassHeader(TransferTransfer $transferTransfer, string $namespace): string
    {
        $code = "<?php\n\n";
        $code .= "declare(strict_types = 1);\n\n";
        $code .= sprintf("namespace %s;\n\n", $namespace);

        $useTypes = [];

        foreach ($transferTransfer->getProperties() as $property) {
            $propertyType = $this->generatorHelper->getPropertyType($property->getType());

            if (!$this->generatorHelper->isBasicType($propertyType) && $propertyType !== 'array' && !in_array($propertyType, $useTypes, true)) {
                $code .= sprintf("use %s;\n", $propertyType);
                $useTypes[] = $propertyType;
            }
        }

        if ($transferTransfer->getType() === 'user') {
            $code .= sprintf("use %s;\n", 'Symfony\Component\Security\Core\User\UserInterface');
        }

        if ($useTypes) {
            $code .= "\n";
        }

        $code .= sprintf("class %sTransfer", $transferTransfer->getName());

        if ($transferTransfer->getType() === 'user') {
            $code .= sprintf(" implements %s", "UserInterface");
        }

        $code .= "\n{\n";

        return $code;
    }
}