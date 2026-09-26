<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Support;

use PhilippHermes\TransferBundle\Service\TransferService;
use PhilippHermes\TransferBundle\Service\TransferServiceFactory;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;

trait TempDirTrait
{
    protected string $tempDir;

    protected function createTempDir(): void
    {
        $dir = sys_get_temp_dir() . '/transfer-bundle-test-' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);

        $this->tempDir = (string)realpath($dir);
    }

    protected function removeTempDir(): void
    {
        if (isset($this->tempDir) && is_dir($this->tempDir)) {
            $this->removeDir($this->tempDir);
        }
    }

    private function removeDir(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }

        rmdir($dir);
    }

    /**
     * Writes a schema file below the temp dir. $transfers is the inner XML of <transfers>.
     */
    protected function writeSchema(string $relativePath, string $transfers): string
    {
        return $this->writeFile(
            $relativePath,
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n<transfers>\n" . $transfers . "\n</transfers>\n",
        );
    }

    protected function writeFile(string $relativePath, string $content): string
    {
        $path = $this->tempDir . '/' . $relativePath;

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $content);

        return $path;
    }

    /**
     * @param array<string> $schemaDirs relative to the temp dir
     * @param array<string> $excludeDirs relative to the temp dir
     */
    protected function createConfig(array $schemaDirs = ['schemas'], array $excludeDirs = [], string $outputDir = 'output'): GeneratorConfigTransfer
    {
        return (new GeneratorConfigTransfer())
            ->setSchemaDirectories(array_map(fn (string $dir) => $this->tempDir . '/' . $dir, $schemaDirs))
            ->setExcludeDirectories(array_map(fn (string $dir) => $this->tempDir . '/' . $dir, $excludeDirs))
            ->setOutputDirectory($this->tempDir . '/' . $outputDir)
            ->setNamespace('PhilippHermes\\TransferBundle\\Tests\\Generated\\T' . bin2hex(random_bytes(6)));
    }

    protected function parse(GeneratorConfigTransfer $config): TransferCollectionTransfer
    {
        return (new TransferService(new TransferServiceFactory()))->parse($config);
    }

    /**
     * Parses and generates the schemas of $config, loads every generated class and returns the namespace.
     */
    protected function generateAndLoad(GeneratorConfigTransfer $config): string
    {
        $service = new TransferService(new TransferServiceFactory());

        $collection = $service->parse($config);
        self::assertSame([], $collection->getErrors(), 'Unexpected parse errors');

        $service->generate($config, $collection, fn () => null);

        foreach (glob($config->getOutputDirectory() . '/*.php') ?: [] as $file) {
            require $file;
        }

        return $config->getNamespace();
    }

    protected function generatedFile(GeneratorConfigTransfer $config, string $transferName): string
    {
        return (string)file_get_contents($config->getOutputDirectory() . '/' . $transferName . 'Transfer.php');
    }
}
