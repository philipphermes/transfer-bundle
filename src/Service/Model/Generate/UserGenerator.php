<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;

class UserGenerator implements UserGeneratorInterface
{
    /**
     * @param array<array-key, PropertyTransfer> $sensitiveProperties
     * @param string $code
     *
     * @return string
     */
    public function generateEraseCredentials(array $sensitiveProperties, string $code): string
    {
        $code .= "    /**\n";
        $code .= "     * @return void\n";
        $code .= "     */\n";
        $code .= "    public function eraseCredentials(): void\n";
        $code .= "    {\n";

        foreach ($sensitiveProperties as $property) {
            $code .= sprintf(
                "        \$this->%s = null;\n",
                $property->getName(),
            );
        }

        $code .= "    }\n\n";

        return $code;
    }
}