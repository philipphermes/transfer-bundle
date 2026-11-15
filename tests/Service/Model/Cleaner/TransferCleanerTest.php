<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Service\Model\Cleaner;

use PhilippHermes\TransferBundle\Service\Model\Cleaner\TransferCleaner;
use PhilippHermes\TransferBundle\Tests\Support\TempDirTrait;
use PHPUnit\Framework\TestCase;

class TransferCleanerTest extends TestCase
{
    use TempDirTrait;

    private const string GENERATED = "<?php\n\n/**\n * This file is auto-generated.\n */\n\nclass X {}\n";
    private const string HANDWRITTEN = "<?php\n\nclass X {}\n";

    protected function setUp(): void
    {
        $this->createTempDir();
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();
    }

    public function testRemovesStaleGeneratedFiles(): void
    {
        $stale = $this->writeFile('output/StaleTransfer.php', self::GENERATED);

        (new TransferCleaner())->clean($this->createConfig());

        self::assertFileDoesNotExist($stale);
    }

    public function testMissingOutputDirectoryIsIgnored(): void
    {
        (new TransferCleaner())->clean($this->createConfig());

        self::assertDirectoryDoesNotExist($this->tempDir . '/output');
    }

    /**
     * Finding #5: the cleaner recursed into subdirectories.
     */
    public function testDoesNotRecurseIntoSubdirectories(): void
    {
        $nested = $this->writeFile('output/sub/NestedTransfer.php', self::GENERATED);

        (new TransferCleaner())->clean($this->createConfig());

        self::assertFileExists($nested);
    }

    /**
     * Finding #5: hand-written *Transfer.php files must survive a misconfigured output dir.
     */
    public function testKeepsFilesThatAreNotGenerated(): void
    {
        $handwritten = $this->writeFile('output/MoneyTransfer.php', self::HANDWRITTEN);

        (new TransferCleaner())->clean($this->createConfig());

        self::assertFileExists($handwritten);
    }

    /**
     * Code quality (non-atomic generation): freshly generated files are passed as keep-list.
     */
    public function testKeepsFilesFromKeepList(): void
    {
        $keep = $this->writeFile('output/KeepTransfer.php', self::GENERATED);
        $stale = $this->writeFile('output/StaleTransfer.php', self::GENERATED);

        (new TransferCleaner())->clean($this->createConfig(), [$keep]);

        self::assertFileExists($keep);
        self::assertFileDoesNotExist($stale);
    }

    public function testFindStaleDoesNotDeleteAnything(): void
    {
        $keep = $this->writeFile('output/KeepTransfer.php', self::GENERATED);
        $stale = $this->writeFile('output/StaleTransfer.php', self::GENERATED);
        $this->writeFile('output/MoneyTransfer.php', self::HANDWRITTEN);

        self::assertSame([$stale], (new TransferCleaner())->findStale($this->createConfig(), [$keep]));
        self::assertFileExists($stale);
    }
}
