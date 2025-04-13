<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Service;

use ArrayObject;
use PhilippHermes\TransferBundle\Transfer\Transfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollection;
use Symfony\Component\Finder\Finder;

class XmlSchemaParser
{
    /**
     * @param string $schemaDir
     */
    public function __construct(
        protected readonly string $schemaDir,
    )
    {
    }

    /**
     * @return TransferCollection
     */
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
                continue;
            }

            foreach ($xml->transfer as $transferElement) {
                if (!isset($transferElement['name'])) {
                    $collection->addError('name not set');
                }

                if (!isset($transferElement['type'])) {
                    $collection->addError('type not set');
                }

                $transfer = new Transfer();
                $transfer
                    ->setName((string)$transferElement['name'])
                    ->setType((string)$transferElement['type'])
                    ->setSingular($transferElement['singular'] ? (string)$transferElement['singular'] : null)
                    ->setDescription($transferElement['description'] ? (string)$transferElement['description'] : null)
                    ->setIsNullable($transferElement['is_nullable'] ? (string)$transferElement['is_nullable'] === 'true' : false);

                $collection->addTransfer($transfer);
            }
        }

        return $collection;
    }
}