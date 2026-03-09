<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Service;

use PhilippHermes\TransferBundle\Service\TransferService;
use PhilippHermes\TransferBundle\Service\TransferServiceFactory;
use PhilippHermes\TransferBundle\Service\TransferServiceInterface;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PHPUnit\Framework\TestCase;

class TransferServiceTest extends TestCase
{
    private const string OUTPUT_DIR = __DIR__ . '/../Data/Generated';
    private const string NAMESPACE = 'PhilippHermes\TransferBundle\Tests\Data\Generated';

    private TransferServiceInterface $transferService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transferService = new TransferService(
            new TransferServiceFactory(),
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->cleanupGeneratedFiles();
    }

    private function cleanupGeneratedFiles(): void
    {
        if (!is_dir(self::OUTPUT_DIR)) {
            return;
        }

        $files = glob(self::OUTPUT_DIR . '/*.php');
        if ($files) {
            foreach ($files as $file) {
                unlink($file);
            }
        }

        rmdir(self::OUTPUT_DIR);
    }

    private function createConfig(array $schemaDirs = [], array $excludeDirs = []): GeneratorConfigTransfer
    {
        if ($schemaDirs === []) {
            $schemaDirs = [__DIR__ . '/../Data/*/Transfers'];
        }

        return (new GeneratorConfigTransfer())
            ->setSchemaDirectories($schemaDirs)
            ->setExcludeDirectories($excludeDirs)
            ->setOutputDirectory(self::OUTPUT_DIR)
            ->setNamespace(self::NAMESPACE);
    }

    public function testParseFindsAllTransfers(): void
    {
        $config = $this->createConfig([
            __DIR__ . '/../Data/User/Transfers',
            __DIR__ . '/../Data/Address/Transfers',
        ]);

        $collection = $this->transferService->parse($config);

        self::assertCount(0, $collection->getErrors());
        self::assertCount(3, $collection->getTransfers());

        $names = array_map(
            fn ($t) => $t->getName(),
            $collection->getTransfers()->getArrayCopy()
        );

        self::assertContains('User', $names);
        self::assertContains('Address', $names);
        self::assertContains('Country', $names);
    }

    public function testParseMergesTransfersFromMultipleFiles(): void
    {
        $config = $this->createConfig([
            __DIR__ . '/../Data/User/Transfers',
            __DIR__ . '/../Data/Address/Transfers',
        ]);

        $collection = $this->transferService->parse($config);

        $addressTransfer = null;
        foreach ($collection->getTransfers() as $transfer) {
            if ($transfer->getName() === 'Address') {
                $addressTransfer = $transfer;
                break;
            }
        }

        self::assertNotNull($addressTransfer);
        self::assertCount(3, $addressTransfer->getProperties());

        $propertyNames = array_map(
            fn ($p) => $p->getName(),
            $addressTransfer->getProperties()->getArrayCopy()
        );

        self::assertContains('street', $propertyNames);
        self::assertContains('country', $propertyNames);
        self::assertContains('zip', $propertyNames);
    }

    public function testParseWithGlobPattern(): void
    {
        $config = $this->createConfig([__DIR__ . '/../Data/*/Transfers']);

        $collection = $this->transferService->parse($config);

        self::assertCount(0, $collection->getErrors());
        self::assertGreaterThanOrEqual(7, count($collection->getTransfers()));
    }

    public function testParseExcludesDirectories(): void
    {
        $config = $this->createConfig(
            [__DIR__ . '/../Data/*/Transfers'],
            [__DIR__ . '/../Data/Excluded/Transfers']
        );

        $collection = $this->transferService->parse($config);

        $names = array_map(
            fn ($t) => $t->getName(),
            $collection->getTransfers()->getArrayCopy()
        );

        self::assertNotContains('ShouldBeExcluded', $names);
    }

    public function testParseWithEmptyDirectoryReturnsEmptyCollection(): void
    {
        $config = $this->createConfig([__DIR__ . '/../Data/NonExistent']);

        $collection = $this->transferService->parse($config);

        self::assertCount(0, $collection->getTransfers());
    }

    public function testParseDetectsApiAttribute(): void
    {
        $config = $this->createConfig([__DIR__ . '/../Data/Api/Transfers']);

        $collection = $this->transferService->parse($config);

        $productTransfer = null;
        $categoryTransfer = null;

        foreach ($collection->getTransfers() as $transfer) {
            if ($transfer->getName() === 'Product') {
                $productTransfer = $transfer;
            }
            if ($transfer->getName() === 'Category') {
                $categoryTransfer = $transfer;
            }
        }

        self::assertNotNull($productTransfer);
        self::assertNotNull($categoryTransfer);
        self::assertTrue($productTransfer->isApi());
        self::assertTrue($categoryTransfer->isApi());
    }

    public function testParseDetectsApiAlias(): void
    {
        $config = $this->createConfig([__DIR__ . '/../Data/Api/Transfers']);

        $collection = $this->transferService->parse($config);

        $productTransfer = null;
        $categoryTransfer = null;

        foreach ($collection->getTransfers() as $transfer) {
            if ($transfer->getName() === 'Product') {
                $productTransfer = $transfer;
            }
            if ($transfer->getName() === 'Category') {
                $categoryTransfer = $transfer;
            }
        }

        self::assertNotNull($productTransfer);
        self::assertSame('ProductResource', $productTransfer->getApiAlias());
        self::assertNull($categoryTransfer->getApiAlias());
    }

    public function testGenerateCreatesTransferFiles(): void
    {
        $config = $this->createConfig([
            __DIR__ . '/../Data/User/Transfers',
            __DIR__ . '/../Data/Address/Transfers',
        ]);

        $collection = $this->transferService->parse($config);
        $progress = 0;

        $this->transferService->generate($config, $collection, function () use (&$progress) {
            $progress++;
        });

        self::assertFileExists(self::OUTPUT_DIR . '/UserTransfer.php');
        self::assertFileExists(self::OUTPUT_DIR . '/AddressTransfer.php');
        self::assertFileExists(self::OUTPUT_DIR . '/CountryTransfer.php');
        self::assertSame(3, $progress);
    }

    public function testGenerateCreatesWorkingTransferClasses(): void
    {
        $config = $this->createConfig([
            __DIR__ . '/../Data/User/Transfers',
            __DIR__ . '/../Data/Address/Transfers',
        ]);

        $collection = $this->transferService->parse($config);
        $this->transferService->generate($config, $collection, fn () => null);

        require_once self::OUTPUT_DIR . '/CountryTransfer.php';
        require_once self::OUTPUT_DIR . '/AddressTransfer.php';
        require_once self::OUTPUT_DIR . '/UserTransfer.php';

        $user = new \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer();
        $address = new \PhilippHermes\TransferBundle\Tests\Data\Generated\AddressTransfer();
        $country = new \PhilippHermes\TransferBundle\Tests\Data\Generated\CountryTransfer();

        $user->setEmail('test@example.com');
        self::assertSame('test@example.com', $user->getEmail());

        $address->setStreet('Main Street');
        $address->setZip(12345);
        self::assertSame('Main Street', $address->getStreet());
        self::assertSame(12345, $address->getZip());

        $country->setIso('US');
        $address->setCountry($country);
        self::assertSame('US', $address->getCountry()->getIso());

        $user->addAddress($address);
        self::assertCount(1, $user->getAddresses());
    }

    public function testGenerateCreatesApiAliasConstant(): void
    {
        $config = $this->createConfig([__DIR__ . '/../Data/Api/Transfers']);

        $collection = $this->transferService->parse($config);
        $this->transferService->generate($config, $collection, fn () => null);

        $productContent = file_get_contents(self::OUTPUT_DIR . '/ProductTransfer.php');
        $categoryContent = file_get_contents(self::OUTPUT_DIR . '/CategoryTransfer.php');

        self::assertStringContainsString("public const API_ALIAS = 'ProductResource'", $productContent);
        self::assertStringNotContainsString('API_ALIAS', $categoryContent);
    }

    public function testGenerateAddsOpenApiUseStatement(): void
    {
        $config = $this->createConfig([__DIR__ . '/../Data/Api/Transfers']);

        $collection = $this->transferService->parse($config);
        $this->transferService->generate($config, $collection, fn () => null);

        $productContent = file_get_contents(self::OUTPUT_DIR . '/ProductTransfer.php');

        self::assertStringContainsString('use OpenApi\Attributes as OA;', $productContent);
    }

    public function testCleanRemovesGeneratedFiles(): void
    {
        $config = $this->createConfig([__DIR__ . '/../Data/Locale/Transfers']);

        $collection = $this->transferService->parse($config);
        $this->transferService->generate($config, $collection, fn () => null);

        self::assertFileExists(self::OUTPUT_DIR . '/LocaleTransfer.php');

        $this->transferService->clean($config);

        self::assertFileDoesNotExist(self::OUTPUT_DIR . '/LocaleTransfer.php');
    }

    public function testGenerateWithDateTimeProperty(): void
    {
        $config = $this->createConfig([__DIR__ . '/../Data/Address/Transfers']);

        $collection = $this->transferService->parse($config);
        $this->transferService->generate($config, $collection, fn () => null);

        require_once self::OUTPUT_DIR . '/CountryTransfer.php';

        $country = new \PhilippHermes\TransferBundle\Tests\Data\Generated\CountryTransfer();
        $date = new \DateTime('2024-01-15');

        $country->setCreatedAt($date);

        self::assertSame('2024-01-15', $country->getCreatedAt()->format('Y-m-d'));
    }

    public function testGenerateWithArrayProperty(): void
    {
        $config = $this->createConfig([__DIR__ . '/../Data/Foo/Transfers']);

        $collection = $this->transferService->parse($config);
        $this->transferService->generate($config, $collection, fn () => null);

        require_once self::OUTPUT_DIR . '/BarTransfer.php';
        require_once self::OUTPUT_DIR . '/FooTransfer.php';

        $foo = new \PhilippHermes\TransferBundle\Tests\Data\Generated\FooTransfer();

        $foo->setBar(['item1', 'item2']);
        self::assertSame(['item1', 'item2'], $foo->getBar());

        $foo->setBar([]);
        $foo->addBar('newItem');
        self::assertContains('newItem', $foo->getBar());
    }

    public function testGenerateWithNestedTransferArray(): void
    {
        $config = $this->createConfig([__DIR__ . '/../Data/Locale/Transfers']);

        $collection = $this->transferService->parse($config);
        $this->transferService->generate($config, $collection, fn () => null);

        require_once self::OUTPUT_DIR . '/LocaleTransfer.php';
        require_once self::OUTPUT_DIR . '/LocaleCollectionTransfer.php';

        $locale1 = new \PhilippHermes\TransferBundle\Tests\Data\Generated\LocaleTransfer();
        $locale1->setCode('en_US');

        $locale2 = new \PhilippHermes\TransferBundle\Tests\Data\Generated\LocaleTransfer();
        $locale2->setCode('de_DE');

        $collection = new \PhilippHermes\TransferBundle\Tests\Data\Generated\LocaleCollectionTransfer();
        $collection->addLocale($locale1);
        $collection->addLocale($locale2);

        self::assertCount(2, $collection->getLocales());
        self::assertSame('en_US', $collection->getLocales()->offsetGet(0)->getCode());
        self::assertSame('de_DE', $collection->getLocales()->offsetGet(1)->getCode());
    }

    public function testMultipleSchemaDirectoriesWithGlob(): void
    {
        $config = $this->createConfig([
            __DIR__ . '/../Data/User/Transfers',
            __DIR__ . '/../Data/Foo/Transfers',
        ]);

        $collection = $this->transferService->parse($config);

        $names = array_map(
            fn ($t) => $t->getName(),
            $collection->getTransfers()->getArrayCopy()
        );

        self::assertContains('User', $names);
        self::assertContains('Foo', $names);
        self::assertContains('Bar', $names);
    }
}