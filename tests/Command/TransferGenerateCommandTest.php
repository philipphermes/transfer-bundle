<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Command;

use PhilippHermes\TransferBundle\Command\TransferGenerateCommand;
use PhilippHermes\TransferBundle\Service\TransferService;
use PhilippHermes\TransferBundle\Service\TransferServiceFactory;
use PhilippHermes\TransferBundle\Service\TransferServiceInterface;
use PhilippHermes\TransferBundle\Tests\Support\TempDirTrait;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TransferGenerateCommandTest extends TestCase
{
    use TempDirTrait;

    private const string STALE = "<?php\n\n/**\n * This file is auto-generated.\n */\n\nclass StaleTransfer {}\n";

    protected function setUp(): void
    {
        $this->createTempDir();
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();
    }

    public function testGeneratesTransfersAndRemovesStaleFiles(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string"/></transfer>');
        $stale = $this->writeFile('output/StaleTransfer.php', self::STALE);

        $tester = $this->execute();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Transfer generation completed successfully', $tester->getDisplay());
        self::assertFileExists($this->tempDir . '/output/UserTransfer.php');
        self::assertFileDoesNotExist($stale);
    }

    public function testCleanDisableKeepsStaleFiles(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string"/></transfer>');
        $stale = $this->writeFile('output/StaleTransfer.php', self::STALE);

        $tester = $this->execute(['--clean-disable' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertFileExists($stale);
    }

    public function testParseErrorsFailWithoutTouchingOutput(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="Order"><property name="price" type="Money"/></transfer>');
        $stale = $this->writeFile('output/StaleTransfer.php', self::STALE);

        $tester = $this->execute();

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Money', $tester->getDisplay());
        self::assertFileExists($stale);
    }

    /**
     * Low: warnings (e.g. a schema dir matching nothing) are printed.
     */
    public function testPrintsWarnings(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string"/></transfer>');

        $tester = $this->execute([], ['schemas', 'missing']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('did not match any directory', $tester->getDisplay());
    }

    public function testCheckFailsWithoutWritingWhenOutputIsMissing(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string"/></transfer>');

        $tester = $this->execute(['--check' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('new: ' . $this->tempDir . '/output/UserTransfer.php', $tester->getDisplay());
        self::assertDirectoryDoesNotExist($this->tempDir . '/output');
    }

    public function testCheckSucceedsAfterGeneration(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string"/></transfer>');
        $command = $this->createCommand(new TransferService(new TransferServiceFactory()));

        (new CommandTester($command))->execute([]);
        $tester = new CommandTester($command);
        $tester->execute(['--check' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('up to date', $tester->getDisplay());
    }

    public function testCheckReportsChangedAndStaleFiles(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string"/></transfer>');
        $user = $this->writeFile('output/UserTransfer.php', 'outdated');
        $stale = $this->writeFile('output/StaleTransfer.php', self::STALE);

        $tester = $this->execute(['--check' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('changed: ' . $user, $tester->getDisplay());
        self::assertStringContainsString('stale: ' . $stale, $tester->getDisplay());
        self::assertSame('outdated', file_get_contents($user));
        self::assertFileExists($stale);
    }

    public function testCheckIgnoresStaleFilesWhenCleanIsDisabled(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string"/></transfer>');
        $command = $this->createCommand(new TransferService(new TransferServiceFactory()));
        (new CommandTester($command))->execute([]);
        $this->writeFile('output/StaleTransfer.php', self::STALE);

        $tester = new CommandTester($command);
        $tester->execute(['--check' => true, '--clean-disable' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    /**
     * Code quality (non-atomic generation): the output dir must not be cleaned before generation succeeded.
     */
    public function testDoesNotCleanWhenGenerationFails(): void
    {
        $collection = (new TransferCollectionTransfer())->addTransfer((new TransferTransfer())->setName('User'));

        $service = $this->createMock(TransferServiceInterface::class);
        $service->method('parse')->willReturn($collection);
        $service->method('generate')->willThrowException(new RuntimeException('boom'));
        $service->expects(self::never())->method('clean');

        $this->expectException(RuntimeException::class);

        (new CommandTester($this->createCommand($service)))->execute([]);
    }

    /**
     * Code quality: configure() overrode the #[AsCommand] description.
     */
    public function testUsesDescriptionFromAsCommandAttribute(): void
    {
        $command = $this->createCommand(new TransferService(new TransferServiceFactory()));

        self::assertSame('transfer:generate', $command->getName());
        self::assertSame('Generate Transfers from XML schemas', $command->getDescription());
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string> $schemaDirs
     */
    private function execute(array $input = [], array $schemaDirs = ['schemas']): CommandTester
    {
        $tester = new CommandTester($this->createCommand(new TransferService(new TransferServiceFactory()), $schemaDirs));
        $tester->execute($input);

        return $tester;
    }

    /**
     * @param array<string> $schemaDirs
     */
    private function createCommand(TransferServiceInterface $service, array $schemaDirs = ['schemas']): TransferGenerateCommand
    {
        $config = $this->createConfig($schemaDirs);

        return new TransferGenerateCommand(
            $service,
            $config->getSchemaDirectories(),
            $config->getExcludeDirectories(),
            $config->getOutputDirectory(),
            $config->getNamespace(),
        );
    }
}
