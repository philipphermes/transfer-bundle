<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\ClassGeneratorSteps;

use BackedEnum;
use DateTimeInterface;
use UnitEnum;

abstract class AbstractClassGeneratorStep implements ClassGeneratorStepInterface
{
    /**
     * @param string $type
     *
     * @return bool
     */
    protected function isDateTime(string $type): bool
    {
        return $type === DateTimeInterface::class || is_subclass_of($type, DateTimeInterface::class);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    protected function isBackedEnum(string $type): bool
    {
        return is_subclass_of($type, BackedEnum::class);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    protected function isUnitEnum(string $type): bool
    {
        return !$this->isBackedEnum($type) && is_subclass_of($type, UnitEnum::class);
    }

    /**
     * Tags a class name in method bodies, so the printer shortens it according to the namespace and its imports.
     *
     * @param string $type
     *
     * @return string
     */
    protected function className(string $type): string
    {
        return '/*(n*/' . ltrim($type, '\\');
    }
}
