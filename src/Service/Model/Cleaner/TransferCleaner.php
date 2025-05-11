<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Cleaner;

use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use Symfony\Component\Finder\Finder;

readonly class TransferCleaner implements TransferCleanerInterface
{
    /**
     * @inheritDoc
     */
    public function clean(GeneratorConfigTransfer $generatorConfigTransfer): void
    {
        if (!is_dir($generatorConfigTransfer->getOutputDirectory())) {
            return;
        }

        $finder = new Finder();
        $finder->files()->in($generatorConfigTransfer->getOutputDirectory())->name('*Transfer.php');

        if (!$finder->hasResults()) {
            return;
        }

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();
            unlink($absoluteFilePath);
        }
    }
}