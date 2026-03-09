<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Service\Model\Parser;

use PhilippHermes\TransferBundle\Service\Model\Type\PropertyTypeMapper;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;
use Symfony\Component\Finder\Finder;

readonly class TransferParser implements TransferParserInterface
{
    public function __construct(
        protected PropertyTypeMapper $propertyTypeMapper,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function parse(GeneratorConfigTransfer $generatorConfigTransfer): TransferCollectionTransfer
    {
        $collection = new TransferCollectionTransfer();

        $allPaths = [];
        foreach ($generatorConfigTransfer->getSchemaDirectories() as $schemaDirectory) {
            $paths = glob($schemaDirectory, GLOB_ONLYDIR);

            if ($paths === false) {
                $collection->addError('Could not parse ' . $schemaDirectory);
                continue;
            }

            $allPaths = array_merge($allPaths, $paths);
        }

        if ($allPaths === []) {
            return $collection;
        }

        $excludePatterns = [];
        foreach ($generatorConfigTransfer->getExcludeDirectories() as $excludeDirectory) {
            $excludedPaths = glob($excludeDirectory, GLOB_ONLYDIR);
            if ($excludedPaths !== false) {
                $excludePatterns = array_merge($excludePatterns, $excludedPaths);
            }
        }

        $allPaths = array_filter($allPaths, function (string $path) use ($excludePatterns): bool {
            foreach ($excludePatterns as $excludePattern) {
                if (str_starts_with($path, $excludePattern) || $path === $excludePattern) {
                    return false;
                }
            }
            return true;
        });

        if ($allPaths === []) {
            return $collection;
        }

        $finder = new Finder();
        $finder->files()->in($allPaths)->name('*.xml');

        if (!$finder->hasResults()) {
            return $collection;
        }

        /**
         * @var array<string, string> $propertyTypesToResolve
         */
        $propertyTypesToResolve = [];

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();

            $xml = simplexml_load_file($absoluteFilePath);
            if (!$xml || !isset($xml->transfer)) {
                $collection->addError("Invalid XML structure in '{$file->getFilename()}'");
                continue;
            }

            foreach ($xml->transfer as $transferElement) {
                if (!isset($transferElement['name'])) {
                    $collection->addError("Missing 'name' attribute in file '{$file->getFilename()}'");

                    continue;
                }

                $transfer = $this->getTransferFromCollection((string)$transferElement['name'], $collection);

                if (!$transfer) {
                    $transfer = new TransferTransfer();
                    $transfer->setName((string)$transferElement['name']);
                    $transfer->setIsApi(isset($transferElement['api']) && ((string)$transferElement['api'] === 'true'));
                }

                foreach ($transferElement->property as $propertyElement) {
                    $skip = false;

                    if (!isset($propertyElement['name'])) {
                        $collection->addError(sprintf(
                            "Missing 'name' attribute in transfer '%s' in file '%s'",
                            $transferElement['name'] ?? '',
                            $file->getFilename(),
                        ));

                        $skip = true;
                    }

                    if (!isset($propertyElement['type'])) {
                        $collection->addError(sprintf(
                            "Missing 'type' attribute in transfer '%s' in file '%s'",
                            $transferElement['name'] ?? '',
                            $file->getFilename(),
                        ));

                        $skip = true;
                    }

                    if ($skip) {
                        continue;
                    }

                    $property = (new PropertyTransfer())->setName((string)$propertyElement['name']);
                    $propertyTypeToResolveKey = $this->getPropertyTypeToResolveKey($transfer, $property);

                    if (!isset($propertyTypesToResolve[$propertyTypeToResolveKey])) {
                        $property
                            ->setDescription(isset($propertyElement['description']) ? (string)$propertyElement['description'] : null)
                            ->setSingular(isset($propertyElement['singular']) ? (string)$propertyElement['singular'] : null)
                            ->setIsNullable(isset($propertyElement['isNullable']) && ((string)$propertyElement['isNullable'] === 'true'));

                        $transfer->addProperty($property);
                        $propertyTypesToResolve[$propertyTypeToResolveKey] = (string)$propertyElement['type'];
                    }
                }

                if (!$this->getTransferFromCollection($transfer->getName(), $collection)) {
                    $collection->addTransfer($transfer);
                }
            }
        }

        $this->propertyTypeMapper->setDefinedTransfers($collection);

        foreach ($collection->getTransfers() as $transfer) {
            foreach ($transfer->getProperties() as $property) {
                $propertyTypeToResolveKey = $this->getPropertyTypeToResolveKey($transfer, $property);

                if (isset($propertyTypesToResolve[$propertyTypeToResolveKey])) {
                    $this->propertyTypeMapper->addTypes(
                        $generatorConfigTransfer,
                        $property,
                        $propertyTypesToResolve[$propertyTypeToResolveKey]
                    );
                }
            }
        }

        return $collection;
    }

    /**
     * @param string $name
     * @param TransferCollectionTransfer $transferCollectionTransfer
     *
     * @return TransferTransfer|null
     */
    protected function getTransferFromCollection(string $name, TransferCollectionTransfer $transferCollectionTransfer): ?TransferTransfer
    {
        foreach ($transferCollectionTransfer->getTransfers() as $transferTransfer) {
            if ($transferTransfer->getName() === $name) {
                return $transferTransfer;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param TransferTransfer $transferTransfer
     *
     * @return bool
     */
    protected function hasProperty(string $name, TransferTransfer $transferTransfer): bool
    {
        foreach ($transferTransfer->getProperties() as $propertyTransfer) {
            if ($propertyTransfer->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TransferTransfer $transferTransfer
     * @param PropertyTransfer $propertyTransfer
     *
     * @return string
     */
    protected function getPropertyTypeToResolveKey(TransferTransfer $transferTransfer, PropertyTransfer $propertyTransfer): string
    {
        return sprintf(
            '%s_%s',
            $transferTransfer->getName(),
            $propertyTransfer->getName(),
        );
    }
}
