<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps\PropertyGeneratorStepInterface;
use PhilippHermes\TransferBundle\Service\Model\Type\PropertyTypeMapper;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;
use RuntimeException;

class Generator implements GeneratorInterface
{
    public const string FILE_HEADER = 'This file is auto-generated.';

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
    ): array {
        $outputDirectory = $generatorConfigTransfer->getOutputDirectory();

        $files = [];
        foreach ($transferCollectionTransfer->getTransfers() as $transfer) {
            $files[$outputDirectory . '/' . $transfer->getName() . 'Transfer.php'] = $this->renderTransfer($generatorConfigTransfer, $transfer);
        }

        if (!is_dir($outputDirectory) && !@mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
            throw new RuntimeException(sprintf("Could not create output directory '%s': %s", $outputDirectory, $this->getLastErrorMessage()));
        }

        foreach ($files as $path => $content) {
            if (@file_put_contents($path, $content) === false) {
                throw new RuntimeException(sprintf("Could not write '%s': %s", $path, $this->getLastErrorMessage()));
            }

            $progressCallback();
        }

        return array_keys($files);
    }

    /**
     * @return string
     */
    protected function getLastErrorMessage(): string
    {
        return error_get_last()['message'] ?? 'unknown error';
    }

    /**
     * @param GeneratorConfigTransfer $generatorConfig
     * @param TransferTransfer $transfer
     *
     * @return string
     */
    protected function renderTransfer(GeneratorConfigTransfer $generatorConfig, TransferTransfer $transfer): string
    {
        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addComment(self::FILE_HEADER);

        $className = $transfer->getName() . 'Transfer';
        $namespace = $file->addNamespace($generatorConfig->getNamespace());
        $this->generateUses($transfer, $namespace, $className);

        $class = $namespace->addClass($className);

        if ($transfer->isApi()) {
            $alias = $transfer->getApiAlias() ?? $transfer->getName();
            $class->addConstant('API_ALIAS', $alias)->setPublic();
        }

        foreach ($transfer->getProperties() as $property) {
            foreach ($this->propertyGeneratorSteps as $propertyGeneratorStep) {
                $propertyGeneratorStep->generate($transfer, $property, $class);
            }
        }

        return (string)$file;
    }

    /**
     * Imports all referenced classes. Transfers live in the generated namespace and need no import.
     *
     * @param TransferTransfer $transfer
     * @param PhpNamespace $namespace
     * @param string $className
     *
     * @return void
     */
    protected function generateUses(TransferTransfer $transfer, PhpNamespace $namespace, string $className): void
    {
        foreach ($transfer->getProperties() as $propertyTransfer) {
            if (!$propertyTransfer->isTransfer()) {
                $this->addUse($namespace, $propertyTransfer->getType(), $className);
            }

            if ($propertyTransfer->getSingularType() && !$propertyTransfer->isSingularTransfer()) {
                $this->addUse($namespace, $propertyTransfer->getSingularType(), $className);
            }
        }

        if ($transfer->isApi()) {
            $namespace->addUse('OpenApi\Attributes', 'OA');
        }
    }

    /**
     * @param PhpNamespace $namespace
     * @param string $type
     * @param string $className
     *
     * @return void
     */
    protected function addUse(PhpNamespace $namespace, string $type, string $className): void
    {
        if (in_array($type, PropertyTypeMapper::PHP_TYPES, true)) {
            return;
        }

        $parts = explode('\\', $type);
        $shortName = end($parts);
        $uses = $namespace->getUses();

        if ($shortName === $className || (isset($uses[$shortName]) && $uses[$shortName] !== $type)) {
            return;
        }

        $namespace->addUse($type);
    }
}
