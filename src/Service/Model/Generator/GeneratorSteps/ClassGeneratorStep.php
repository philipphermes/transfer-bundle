<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class ClassGeneratorStep implements GeneratorStepInterface
{
    /**
     * @inheritDoc
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer, TransferTransfer $transferTransfer, string $code): string
    {
        $code .= "<?php\n\n";
        $code .= "declare(strict_types = 1);\n\n";
        $code .= sprintf("namespace %s;\n\n", $generatorConfigTransfer->getNamespace());

        $useTypes = [];

        foreach ($transferTransfer->getProperties() as $property) {
            if (!$property->getIsBasic() && $property->getRealType() !== 'array' && !str_contains($property->getRealType(), 'Transfer') && !in_array($property->getRealType(), $useTypes, true)) {
                $useTypes[] = $property->getRealType();
            }
        }

        if ($transferTransfer->getType() === 'user') {
            $useTypes[] = 'Symfony\Component\Security\Core\User\UserInterface';
        }

        if ($useTypes) {
            sort($useTypes, SORT_STRING);

            foreach ($useTypes as $useType) {
                $code .= sprintf("use %s;\n", $useType);
            }

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