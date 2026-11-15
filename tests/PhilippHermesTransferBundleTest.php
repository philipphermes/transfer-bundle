<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests;

use PhilippHermes\TransferBundle\PhilippHermesTransferBundle;
use PhilippHermes\TransferBundle\Tests\Support\TempDirTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\HttpKernel\DependencyInjection\MergeExtensionConfigurationPass;

class PhilippHermesTransferBundleTest extends TestCase
{
    use TempDirTrait;

    protected function setUp(): void
    {
        $this->createTempDir();
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();
    }

    public function testRegistersApiModelsFromDefaultOutputDir(): void
    {
        $this->writeApiTransfer('src/Generated/Transfers/UserTransfer.php', 'App\\Generated\\Transfers', 'User', 'UserResource');

        self::assertSame(
            [['alias' => 'UserResource', 'type' => 'App\\Generated\\Transfers\\UserTransfer']],
            $this->nelmioModels([]),
        );
    }

    /**
     * Finding #4: custom output_dir/namespace were ignored.
     */
    public function testRegistersApiModelsFromCustomOutputDirAndNamespace(): void
    {
        $this->writeApiTransfer('custom/UserTransfer.php', 'Acme\\Dto', 'User', 'UserResource');

        self::assertSame(
            [['alias' => 'UserResource', 'type' => 'Acme\\Dto\\UserTransfer']],
            $this->nelmioModels(['output_dir' => $this->tempDir . '/custom', 'namespace' => 'Acme\\Dto']),
        );
    }

    /**
     * Finding #4: %kernel.project_dir% in a custom output_dir must be resolved.
     */
    public function testResolvesProjectDirPlaceholderInCustomOutputDir(): void
    {
        $this->writeApiTransfer('custom/UserTransfer.php', 'App\\Generated\\Transfers', 'User', 'UserResource');

        self::assertSame(
            [['alias' => 'UserResource', 'type' => 'App\\Generated\\Transfers\\UserTransfer']],
            $this->nelmioModels(['output_dir' => '%kernel.project_dir%/custom']),
        );
    }

    /**
     * Low: aliases containing an escaped quote were cut off.
     */
    public function testReadsAliasWithEscapedQuote(): void
    {
        $this->writeApiTransfer('src/Generated/Transfers/UserTransfer.php', 'App\\Generated\\Transfers', 'User', "O\\'Brien");

        self::assertSame("O'Brien", $this->nelmioModels([])[0]['alias']);
    }

    public function testIgnoresNonApiTransfers(): void
    {
        $this->writeFile('src/Generated/Transfers/UserTransfer.php', "<?php\nnamespace App\\Generated\\Transfers;\nclass UserTransfer {}\n");

        self::assertSame([], $this->nelmioModels([]));
    }

    public function testAliasFallsBackToClassNameWithoutConstant(): void
    {
        $this->writeFile('src/Generated/Transfers/UserTransfer.php', "<?php\nnamespace App\\Generated\\Transfers;\nuse OpenApi\\Attributes as OA;\nclass UserTransfer {}\n");

        self::assertSame(
            [['alias' => 'User', 'type' => 'App\\Generated\\Transfers\\UserTransfer']],
            $this->nelmioModels([]),
        );
    }

    public function testMissingOutputDirectoryRegistersNothing(): void
    {
        self::assertSame([], $this->nelmioModels([]));
    }

    public function testEmptyOutputDirectoryRegistersNothing(): void
    {
        mkdir($this->tempDir . '/src/Generated/Transfers', 0777, true);

        self::assertSame([], $this->nelmioModels([]));
    }

    /**
     * The prepend phase must not fail on an unknown parameter; Symfony reports it later when loading the extension.
     */
    public function testUnresolvableParameterIsIgnoredWhilePrepending(): void
    {
        $container = $this->createContainer(['output_dir' => '%unknown_parameter%/transfers']);

        try {
            (new MergeExtensionConfigurationPass(['transfer']))->process($container);
            self::fail('Expected the extension loading to fail');
        } catch (ParameterNotFoundException $exception) {
            self::assertStringContainsString('while loading extension "transfer"', $exception->getMessage());
        }

        self::assertSame([], $container->getExtensionConfig('nelmio_api_doc'));
    }

    /**
     * @param array<string, mixed> $transferConfig
     *
     * @return array<array{alias: string, type: string}>
     */
    private function nelmioModels(array $transferConfig): array
    {
        $container = $this->createContainer($transferConfig);

        (new MergeExtensionConfigurationPass(['transfer']))->process($container);

        $config = $container->getExtensionConfig('nelmio_api_doc');

        return $config[0]['models']['names'] ?? [];
    }

    /**
     * @param array<string, mixed> $transferConfig
     */
    private function createContainer(array $transferConfig): ContainerBuilder
    {
        $bundle = new PhilippHermesTransferBundle();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->tempDir);
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.build_dir', $this->tempDir);
        $container->setParameter('kernel.bundles_metadata', []);

        $extension = $bundle->getContainerExtension();
        self::assertNotNull($extension);
        $container->registerExtension($extension);
        $container->loadFromExtension('transfer', $transferConfig);

        return $container;
    }

    private function writeApiTransfer(string $path, string $namespace, string $name, string $alias): void
    {
        $this->writeFile($path, <<<PHP
            <?php

            namespace {$namespace};

            use OpenApi\Attributes as OA;

            class {$name}Transfer
            {
                public const API_ALIAS = '{$alias}';
            }
            PHP);
    }
}
