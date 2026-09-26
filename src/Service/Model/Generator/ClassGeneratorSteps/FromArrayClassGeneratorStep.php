<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\ClassGeneratorSteps;

use DateTime;
use DateTimeImmutable;
use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class FromArrayClassGeneratorStep extends AbstractClassGeneratorStep
{
    /**
     * @inheritDoc
     */
    public function generate(TransferTransfer $transferTransfer, ClassType $class): void
    {
        $method = $class->addMethod('fromArray');
        $method->setPublic();
        $method->setStatic();
        $method->setReturnType('self');
        $method->addParameter('data')->setType('array');
        $method->addComment('Creates a transfer from an array as returned by toArray(). Missing keys are left unset, unknown keys are ignored.');
        $method->addComment('');
        $method->addComment('@param array<string, mixed> $data');
        $method->addComment('');
        $method->addComment('@return self');

        $method->addBody('$transfer = new self();');

        foreach ($transferTransfer->getProperties() as $property) {
            $method->addBody('');
            $method->addBody(sprintf("if (array_key_exists('%s', \$data)) {", $property->getName()));
            $method->addBody(sprintf(
                "\t\$transfer->set%s(%s);",
                ucfirst($property->getName()),
                $this->resolveValue($property, sprintf("\$data['%s']", $property->getName())),
            ));
            $method->addBody('}');
        }

        $method->addBody('');
        $method->addBody('return $transfer;');
    }

    /**
     * @param PropertyTransfer $property
     * @param string $value
     *
     * @return string
     */
    protected function resolveValue(PropertyTransfer $property, string $value): string
    {
        $singularType = $property->getSingularType();

        if ($singularType === null) {
            return $this->convert($value, $property->getType(), $property->isTransfer());
        }

        $item = $this->convert('$item', $singularType, $property->isSingularTransfer());
        $items = $item === '$item' ? $value : sprintf('array_map(fn ($item) => %s, %s)', $item, $value);

        if ($property->getType() !== 'ArrayObject') {
            return $item === '$item' ? $value : sprintf('is_array(%s) ? %s : %s', $value, $items, $value);
        }

        return sprintf('is_array(%1$s) ? new %2$s(%3$s) : %1$s', $value, $this->className('ArrayObject'), $items);
    }

    /**
     * @param string $value
     * @param string $type
     * @param bool $isTransfer
     *
     * @return string
     */
    protected function convert(string $value, string $type, bool $isTransfer): string
    {
        $className = $this->className($type);

        return match (true) {
            $isTransfer => sprintf('is_array(%1$s) ? %2$s::fromArray(%1$s) : %1$s', $value, $className),
            $type === DateTime::class => sprintf('is_string(%1$s) ? new %2$s(%1$s) : %1$s', $value, $this->className(DateTime::class)),
            $this->isDateTime($type) && is_a(DateTimeImmutable::class, $type, true)
                => sprintf('is_string(%1$s) ? new %2$s(%1$s) : %1$s', $value, $this->className(DateTimeImmutable::class)),
            $this->isBackedEnum($type) => sprintf('is_int(%1$s) || is_string(%1$s) ? %2$s::from(%1$s) : %1$s', $value, $className),
            $this->isUnitEnum($type) => sprintf("is_string(%1\$s) ? constant(%2\$s::class . '::' . %1\$s) : %1\$s", $value, $className),
            default => $value,
        };
    }
}
