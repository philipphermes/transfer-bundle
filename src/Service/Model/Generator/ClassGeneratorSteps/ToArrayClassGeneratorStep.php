<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\ClassGeneratorSteps;

use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class ToArrayClassGeneratorStep extends AbstractClassGeneratorStep
{
    /**
     * @inheritDoc
     */
    public function generate(TransferTransfer $transferTransfer, ClassType $class): void
    {
        $method = $class->addMethod('toArray');
        $method->setPublic();
        $method->setReturnType('array');
        $method->addComment('Converts the transfer recursively to an array. Unset properties are null.');
        $method->addComment('');
        $method->addComment('@return array<string, mixed>');

        $method->addBody('return [');
        foreach ($transferTransfer->getProperties() as $property) {
            $method->addBody(sprintf("\t'%s' => %s,", $property->getName(), $this->resolveValue($property)));
        }
        $method->addBody('];');
    }

    /**
     * @param PropertyTransfer $property
     *
     * @return string
     */
    protected function resolveValue(PropertyTransfer $property): string
    {
        $name = $property->getName();
        $singularType = $property->getSingularType();

        if ($singularType === null && $property->isAlwaysInitialized()) {
            return '$this->' . $name;
        }

        if ($singularType === null) {
            $value = $this->convert('($this->' . $name . ' ?? null)', $property->getType(), $property->isTransfer(), true);

            return $value === '($this->' . $name . ' ?? null)' ? '$this->' . $name . ' ?? null' : $value;
        }

        $items = $property->getType() === 'ArrayObject'
            ? '$this->get' . ucfirst($name) . '()->getArrayCopy()'
            : '$this->get' . ucfirst($name) . '()';
        $item = $this->convert('$item', $singularType, $property->isSingularTransfer(), false);

        return $item === '$item' ? $items : sprintf('array_map(fn ($item) => %s, %s)', $item, $items);
    }

    /**
     * @param string $value
     * @param string $type
     * @param bool $isTransfer
     * @param bool $isNullable
     *
     * @return string
     */
    protected function convert(string $value, string $type, bool $isTransfer, bool $isNullable): string
    {
        $operator = $isNullable ? '?->' : '->';

        return match (true) {
            $isTransfer => $value . $operator . 'toArray()',
            $this->isDateTime($type) => $value . $operator . 'format(DATE_ATOM)',
            $this->isBackedEnum($type) => $value . $operator . 'value',
            $this->isUnitEnum($type) => $value . $operator . 'name',
            default => $value,
        };
    }
}
