<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Cleaner;

use PhilippHermes\TransferBundle\Service\Model\Generator\Generator;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use Symfony\Component\Finder\Finder;

readonly class TransferCleaner implements TransferCleanerInterface
{
    /**
     * @inheritDoc
     */
    public function clean(GeneratorConfigTransfer $generatorConfigTransfer, array $keepFiles = []): void
    {
        if (!is_dir($generatorConfigTransfer->getOutputDirectory())) {
            return;
        }

        $keep = [];
        foreach ($keepFiles as $keepFile) {
            $keep[realpath($keepFile) ?: $keepFile] = true;
        }

        $finder = new Finder();
        $finder->files()->in($generatorConfigTransfer->getOutputDirectory())->depth(0)->name('*Transfer.php');

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();

            if (isset($keep[$absoluteFilePath]) || !str_contains($file->getContents(), Generator::FILE_HEADER)) {
                continue;
            }

            unlink($absoluteFilePath);
        }
    }
}
