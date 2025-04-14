<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Service;

use ArrayObject;
use PhilippHermes\TransferBundle\Transfer\Property;
use PhilippHermes\TransferBundle\Transfer\Transfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollection;
use Symfony\Component\Finder\Finder;

class XmlSchemaParser
{
    public function __construct(
        protected readonly string $schemaDir,
    ) {}

    public function parse(): TransferCollection
    {
        $finder = new Finder();
        $finder->files()->in($this->schemaDir)->name('*.xml');

        $collection = new TransferCollection();
        $collection->setTransfers(new ArrayObject());

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

                $transfer = new Transfer();
                $transfer->setName((string)$transferElement['name']);

                foreach ($transferElement->property as $propertyElement) {
                    if (!isset($propertyElement['name']) || !isset($propertyElement['type'])) {
                        $collection->addError("Missing required attributes in property element of '{$file->getFilename()}'");
                        continue;
                    }

                    $property = (new Property())
                        ->setName((string)$propertyElement['name'])
                        ->setType((string)$propertyElement['type'])
                        ->setDescription(isset($propertyElement['description']) ? (string)$propertyElement['description'] : null)
                        ->setSingular(isset($propertyElement['singular']) ? (string)$propertyElement['singular'] : null)
                        ->setIsNullable(isset($propertyElement['isNullable']) && ((string)$propertyElement['isNullable'] === 'true'));

                    $transfer->addProperty($property);
                }

                $collection->addTransfer($transfer);
            }
        }

        return $collection;
    }
}
