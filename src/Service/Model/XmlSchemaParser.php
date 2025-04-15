<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Service\Model;

use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use Symfony\Component\Finder\Finder;

readonly class XmlSchemaParser implements XmlSchemaParserInterface
{
    public function __construct(protected string $schemaDir) {}

    /**
     * @inheritDoc
     */
    public function parse(): TransferCollectionTransfer
    {
        $finder = new Finder();
        $finder->files()->in($this->schemaDir)->name('*.xml');

        $collection = new TransferCollectionTransfer();

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
                    $collection->addError("Missing 'name' attribute in transfer element of '{$file->getFilename()}'");

                    continue;
                }

                $transfer = $this->getTransferFromCollection((string)$transferElement['name'], $collection);

                if (!$transfer) {
                    $transfer = new TransferTransfer();
                    $transfer->setName((string)$transferElement['name']);
                }

                foreach ($transferElement->property as $propertyElement) {
                    if (!isset($propertyElement['name']) || !isset($propertyElement['type'])) {
                        $collection->addError("Missing required attributes in property element of '{$file->getFilename()}'");

                        continue;
                    }

                    if (!$this->hasProperty((string)$propertyElement['name'], $transfer)) {
                        $property = (new PropertyTransfer())
                            ->setName((string)$propertyElement['name'])
                            ->setType((string)$propertyElement['type'])
                            ->setDescription(isset($propertyElement['description']) ? (string)$propertyElement['description'] : null)
                            ->setSingular(isset($propertyElement['singular']) ? (string)$propertyElement['singular'] : null)
                            ->setIsNullable(isset($propertyElement['isNullable']) && ((string)$propertyElement['isNullable'] === 'true'));

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
