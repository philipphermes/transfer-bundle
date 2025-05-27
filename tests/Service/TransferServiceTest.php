<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Service;

use PhilippHermes\TransferBundle\Service\Model\Generate\ClassGenerator;
use PhilippHermes\TransferBundle\Service\Model\Generate\GetterGenerator;
use PhilippHermes\TransferBundle\Service\Model\Generate\PropertyGenerator;
use PhilippHermes\TransferBundle\Service\Model\Generate\SetterGenerator;
use PhilippHermes\TransferBundle\Service\Model\Generate\UserGenerator;
use PhilippHermes\TransferBundle\Service\Model\Generator\Helper\GeneratorHelper;
use PhilippHermes\TransferBundle\Service\Model\TransferGenerator;
use PhilippHermes\TransferBundle\Service\TransferService;
use PhilippHermes\TransferBundle\Service\TransferServiceFactory;
use PhilippHermes\TransferBundle\Service\TransferServiceInterface;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PHPUnit\Framework\TestCase;

class TransferServiceTest extends TestCase
{
    private TransferServiceInterface $transferService;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->transferService = new TransferService(
            new TransferServiceFactory(),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        return;

        unlink(__DIR__ . '/../Data/Generated/AddressTransfer.php');
        unlink(__DIR__ . '/../Data/Generated/UserTransfer.php');
        unlink(__DIR__ . '/../Data/Generated/CountryTransfer.php');
        unlink(__DIR__ . '/../Data/Generated/FooTransfer.php');
        rmdir(__DIR__ . '/../Data/Generated');
    }

    /**
     * @return void
     */
    public function testGenerate(): void
    {
        $config = (new GeneratorConfigTransfer())
            ->setSchemaDirectory(__DIR__ . '/../Data/*/Transfers')
            ->setOutputDirectory(__DIR__ . '/../Data/Generated')
            ->setNamespace('PhilippHermes\TransferBundle\Tests\Data\Generated');

        $transferCollection = $this->transferService->parse($config);

        $this->transferService->generate(
            $config,
            $transferCollection,
        );

        self::assertFileExists(__DIR__ . '/../Data/Generated/AddressTransfer.php');
        self::assertFileExists(__DIR__ . '/../Data/Generated/UserTransfer.php');
        self::assertFileExists(__DIR__ . '/../Data/Generated/CountryTransfer.php');
        self::assertFileExists(__DIR__ . '/../Data/Generated/FooTransfer.php');

        $user = new \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer();
        $address = new \PhilippHermes\TransferBundle\Tests\Data\Generated\AddressTransfer();
        $country = new \PhilippHermes\TransferBundle\Tests\Data\Generated\CountryTransfer();
        $foo = new \PhilippHermes\TransferBundle\Tests\Data\Generated\FooTransfer();

        $address->setStreet('test');
        self::assertSame('test', $address->getStreet());

        $address->setZip(123);
        self::assertSame(123, $address->getZip());

        $country->setIso('de_DE');
        self::assertSame('de_DE', $country->getIso());

        $date = new \DateTime();
        $country->setCreatedAt($date);
        self::assertSame($date->format('Y-m-d'), $country->getCreatedAt()->format('Y-m-d'));

        $address->setCountry($country);
        self::assertSame($country, $address->getCountry());

        $user->setEmail('test@example.com');
        self::assertSame('test@example.com', $user->getEmail());
        self::assertSame('test@example.com', $user->getUserIdentifier());

        $user->setPassword('password');
        self::assertSame('password', $user->getPassword());

        self::assertNull($user->getPlainPassword());
        $user->setPlainPassword('password');
        self::assertSame('password', $user->getPlainPassword());
        $user->eraseCredentials();
        self::assertNull($user->getPlainPassword());

        $user->setAddresses(new \ArrayObject([$address]));
        self::assertSame('test', $user->getAddresses()->offsetGet(0)->getStreet());
        $user->addAddress($address);
        self::assertCount(2, $user->getAddresses());

        $user->setRoles(['ROLE_USER']);
        self::assertSame(['ROLE_USER'], $user->getRoles());

        $user->addRole('ROLE_ADMIN');
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());

        $foo->setBar([
            'foo' => 'bar',
        ]);
        self::assertSame(['foo' => 'bar'], $foo->getBar());
        $foo->setBar([]);
        $foo->addBar('baaaar');
        self::assertSame('baaaar', reset($foo->getBar()));

        //$this->transferService->clean($config);

        self::assertFileDoesNotExist(__DIR__ . '/../Data/Generated/AddressTransfer.php');
        self::assertFileDoesNotExist(__DIR__ . '/../Data/Generated/UserTransfer.php');
        self::assertFileDoesNotExist(__DIR__ . '/../Data/Generated/CountryTransfer.php');
        self::assertFileDoesNotExist(__DIR__ . '/../Data/Generated/FooTransfer.php');
    }
}