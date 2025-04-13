<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Service;

use ArrayObject;
use DOMDocument;
use PhilippHermes\TransferBundle\Transfer\Transfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollection;
use Symfony\Component\Finder\Finder;

class XmlSchemaParser
{
    public function __construct(
        protected readonly string $schemaDir,
        protected readonly ?string $xsdPath = null,
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

            if ($this->xsdPath) {
                $dom = new DOMDocument();
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = false;

                libxml_use_internal_errors(true);
                $dom->load($absoluteFilePath);

                if (!$dom->schemaValidate($this->xsdPath)) {
                    foreach (libxml_get_errors() as $error) {
                        $collection->addError("XSD error in '{$file->getFilename()}': " . trim($error->message));
                    }
                    libxml_clear_errors();
                    continue;
                }
            }

            $xml = simplexml_load_file($absoluteFilePath);
            if (!$xml || !isset($xml->transfer)) {
                $collection->addError("Invalid XML structure in '{$file->getFilename()}'");
                continue;
            }

            foreach ($xml->transfer as $transferElement) {
                if (!isset($transferElement['name']) || !isset($transferElement['type'])) {
                    $collection->addError("Missing required attributes in '{$file->getFilename()}'");
                    continue;
                }

                $transfer = new Transfer();
                $transfer
                    ->setName((string)$transferElement['name'])
                    ->setType((string)$transferElement['type'])
                    ->setSingular($transferElement['singular'] ? (string)$transferElement['singular'] : null)
                    ->setDescription($transferElement['description'] ? (string)$transferElement['description'] : null)
                    ->setIsNullable($transferElement['isNullable'] && ((string)$transferElement['isNullable'] === 'true'));

                $collection->addTransfer($transfer);
            }
        }

        return $collection;
    }
}
