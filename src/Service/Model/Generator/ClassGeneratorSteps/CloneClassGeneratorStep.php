<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator\ClassGeneratorSteps;

use DateTime;
use Nette\PhpGenerator\ClassType;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

/**
 * Makes `clone` deep for nested transfers, collections and mutable dates, so a clone never shares state with the original.
 */
class CloneClassGeneratorStep extends AbstractClassGeneratorStep
{
    /**
     * @inheritDoc
     */
    public function generate(TransferTransfer $transferTransfer, ClassType $class): void
    {
        $statements = [];

        foreach ($transferTransfer->getProperties() as $property) {
            $statement = $this->resolveStatement($property);

            if ($statement !== null) {
                $statements[] = sprintf("if (isset(\$this->%s)) {\n\t%s\n}", $property->getName(), $statement);
            }
        }

        if ($statements === []) {
            return;
        }

        $method = $class->addMethod('__clone');
        $method->setPublic();
        $method->setReturnType('void');
        $method->setBody(implode("\n\n", $statements));
    }

    /**
     * @param PropertyTransfer $property
     *
     * @return string|null
     */
    protected function resolveStatement(PropertyTransfer $property): ?string
    {
        $name = $property->getName();
        $singularType = $property->getSingularType();

        if ($singularType === null) {
            return $this->isCloneable($property->getType(), $property->isTransfer())
                ? sprintf('$this->%1$s = clone $this->%1$s;', $name)
                : null;
        }

        if ($property->getType() !== 'ArrayObject') {
            return null;
        }

        $items = sprintf('$this->%s->getArrayCopy()', $name);

        if ($this->isCloneable($singularType, $property->isSingularTransfer())) {
            $items = sprintf('array_map(fn ($item) => clone $item, %s)', $items);
        }

        return sprintf('$this->%s = new %s(%s);', $name, $this->className('ArrayObject'), $items);
    }

    /**
     * @param string $type
     * @param bool $isTransfer
     *
     * @return bool
     */
    protected function isCloneable(string $type, bool $isTransfer): bool
    {
        return $isTransfer || is_a($type, DateTime::class, true);
    }
}
