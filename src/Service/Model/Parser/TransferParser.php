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

        $paths = glob($generatorConfigTransfer->getSchemaDirectory(), GLOB_ONLYDIR);

        if ($paths === false) {
            return $collection->addError('Could not parse ' . $generatorConfigTransfer->getSchemaDirectory());
        }

        $finder = new Finder();
        $finder->files()->in($paths)->name('*.xml');

        if (!$finder->hasResults()) {
            return $collection;
        }

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
                    $transfer->setType((string)($transferElement['type'] ?? 'default'));
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

                    if (!$this->hasProperty((string)$propertyElement['name'], $transfer)) {
                        $property = (new PropertyTransfer())
                            ->setName((string)$propertyElement['name'])
                            ->setDescription(isset($propertyElement['description']) ? (string)$propertyElement['description'] : null)
                            ->setSingular(isset($propertyElement['singular']) ? (string)$propertyElement['singular'] : null)
                            ->setIsNullable(isset($propertyElement['isNullable']) && ((string)$propertyElement['isNullable'] === 'true'))
                            ->setIsIdentifier(isset($propertyElement['isIdentifier']) && ((string)$propertyElement['isIdentifier'] === 'true'))
                            ->setIsSensitive(isset($propertyElement['isSensitive']) && ((string)$propertyElement['isSensitive'] === 'true'));

                        $property = $this->propertyTypeMapper->addTypes($generatorConfigTransfer, $property, (string)$propertyElement['type']);

                        $transfer->addProperty($property);
                    }
                }

                if (!$this->getTransferFromCollection($transfer->getName(), $collection)) {
                    $collection->addTransfer($transfer);
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
}
