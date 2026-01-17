<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps\PropertyGeneratorStepInterface;
use PhilippHermes\TransferBundle\Service\Model\Type\PropertyTypeMapper;
use PhilippHermes\TransferBundle\Transfer\AbstractTransfer;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class Generator implements GeneratorInterface
{
    /**
     * @param array<PropertyGeneratorStepInterface> $propertyGeneratorSteps
     */
    public function __construct(
        protected readonly array $propertyGeneratorSteps,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function generate(
        GeneratorConfigTransfer $generatorConfigTransfer,
        TransferCollectionTransfer $transferCollectionTransfer,
        callable $progressCallback,
    ): void {
        foreach ($transferCollectionTransfer->getTransfers() as $transfer) {
            $this->generateTransfer($generatorConfigTransfer, $transfer);
            $progressCallback();
        }
    }

    /**
     * @param GeneratorConfigTransfer $generatorConfig
     * @param TransferTransfer $transfer
     * @return void
     */
    protected function generateTransfer(GeneratorConfigTransfer $generatorConfig, TransferTransfer $transfer)
    {
        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addComment('This file is auto-generated.');

        $namespace = $file->addNamespace($generatorConfig->getNamespace());
        $namespace->addUse(AbstractTransfer::class);
        $this->generateUses($transfer, $namespace);

        $class = $namespace->addClass($transfer->getName() . 'Transfer');
        $class->setExtends(AbstractTransfer::class);

        foreach ($transfer->getProperties() as $property) {
            foreach ($this->propertyGeneratorSteps as $propertyGeneratorStep) {
                $propertyGeneratorStep->generate($transfer, $property, $class);
            }
        }

        if (!is_dir($generatorConfig->getOutputDirectory())) {
            mkdir($generatorConfig->getOutputDirectory(), 0777, true);
        }

        file_put_contents($generatorConfig->getOutputDirectory() . '/' . $transfer->getName() . 'Transfer.php', (string)$file);
    }

    /**
     * @param TransferTransfer $transfer
     * @param PhpNamespace $namespace
     * @return void
     */
    protected function generateUses(TransferTransfer $transfer, PhpNamespace $namespace): void
    {
        foreach ($transfer->getProperties() as $propertyTransfer) {
            if (!in_array($propertyTransfer->getType(), PropertyTypeMapper::PHP_TYPES, true) && !str_contains($propertyTransfer->getType(), 'Transfer')) {
                $namespace->addUse($propertyTransfer->getType());
            }

            if ($propertyTransfer->getSingularType() && !in_array($propertyTransfer->getSingularType(), PropertyTypeMapper::PHP_TYPES, true) && !str_contains($propertyTransfer->getSingularType(), 'Transfer')) {
                $namespace->addUse($propertyTransfer->getSingularType());
            }
        }

        if ($transfer->isApi()) {
            $namespace->addUse('OpenApi\Attributes', 'OA');
        }
    }
}