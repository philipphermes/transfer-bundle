<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model;

use Symfony\Component\Finder\Finder;

readonly class TransferCleaner implements TransferCleanerInterface
{
    public function __construct(
        protected string $outputDir,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function clean(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->outputDir)->name('*Transfer.php');

        if (!$finder->hasResults()) {
            return;
        }

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();

            unlink($absoluteFilePath);
        }
    }
}