<?php

namespace PhilippHermes\TransferBundle\Service\Model\Generate;

use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;

interface SetterGeneratorInterface
{
    /**
     * @param PropertyTransfer $property
     * @param string $code
     *
     * @return string
     */
    public function generateSetter(PropertyTransfer $property, string $code): string;

    /**
     * @param PropertyTransfer $property
     * @param string $code
     *
     * @return string
     */
    public function generateAdder(PropertyTransfer $property, string $code): string;
}