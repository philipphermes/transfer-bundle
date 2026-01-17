<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Transfer;

use PhilippHermes\TransferBundle\Service\TransferService;
use PhilippHermes\TransferBundle\Service\TransferServiceFactory;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PHPUnit\Framework\TestCase;

class AbstractTransferTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $transferService = new TransferService(new TransferServiceFactory());
        $config = (new GeneratorConfigTransfer())
            ->setSchemaDirectory(__DIR__ . '/../Data/*/Transfers')
            ->setOutputDirectory(__DIR__ . '/../Data/Generated')
            ->setNamespace('PhilippHermes\TransferBundle\Tests\Data\Generated');

        $transferCollection = $transferService->parse($config);
        $transferService->generate($config, $transferCollection, fn() => null);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unlink(__DIR__ . '/../Data/Generated/AddressTransfer.php');
        unlink(__DIR__ . '/../Data/Generated/UserTransfer.php');
        unlink(__DIR__ . '/../Data/Generated/CountryTransfer.php');
        unlink(__DIR__ . '/../Data/Generated/FooTransfer.php');
        unlink(__DIR__ . '/../Data/Generated/BarTransfer.php');
        unlink(__DIR__ . '/../Data/Generated/LocaleTransfer.php');
        unlink(__DIR__ . '/../Data/Generated/LocaleCollectionTransfer.php');
        rmdir(__DIR__ . '/../Data/Generated');
    }

    /**
     * @return void
     */
    public function testToArrayCamelCase(): void
    {
        $user = new \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer();
        $user->setEmail('test@example.com');
        $user->setPassword('secret');

        $array = $user->toArray('camelCase', false);

        self::assertArrayHasKey('email', $array);
        self::assertArrayHasKey('password', $array);
        self::assertSame('test@example.com', $array['email']);
        self::assertSame('secret', $array['password']);
    }

    /**
     * @return void
     */
    public function testToArraySnakeCase(): void
    {
        $user = new \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer();
        $user->setEmail('test@example.com');
        $user->setPassword('secret');

        $array = $user->toArray('snake_case', false);

        self::assertArrayHasKey('email', $array);
        self::assertArrayHasKey('password', $array);
        self::assertSame('test@example.com', $array['email']);
        self::assertSame('secret', $array['password']);
    }

    /**
     * @return void
     */
    public function testToArrayRecursive(): void
    {
        $country = new \PhilippHermes\TransferBundle\Tests\Data\Generated\CountryTransfer();
        $country->setIso('de_DE');

        $address = new \PhilippHermes\TransferBundle\Tests\Data\Generated\AddressTransfer();
        $address->setStreet('Main Street');
        $address->setZip(12345);
        $address->setCountry($country);

        $user = new \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer();
        $user->setEmail('test@example.com');
        $user->addAddress($address);

        $array = $user->toArray('camelCase', true);

        self::assertArrayHasKey('email', $array);
        self::assertArrayHasKey('addresses', $array);
        self::assertIsArray($array['addresses']);
        self::assertCount(1, $array['addresses']);
        self::assertArrayHasKey('street', $array['addresses'][0]);
        self::assertSame('Main Street', $array['addresses'][0]['street']);
        self::assertArrayHasKey('country', $array['addresses'][0]);
        self::assertArrayHasKey('iso', $array['addresses'][0]['country']);
        self::assertSame('de_DE', $array['addresses'][0]['country']['iso']);
    }

    /**
     * @return void
     */
    public function testFromArrayCamelCase(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'secret',
        ];

        $user = new \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer();
        $user->fromArray($data, 'camelCase', false);

        self::assertSame('test@example.com', $user->getEmail());
        self::assertSame('secret', $user->getPassword());
    }

    /**
     * @return void
     */
    public function testFromArraySnakeCase(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'secret',
        ];

        $user = new \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer();
        $user->fromArray($data, 'snake_case', false);

        self::assertSame('test@example.com', $user->getEmail());
        self::assertSame('secret', $user->getPassword());
    }

    /**
     * @return void
     */
    public function testFromArrayRecursive(): void
    {
        $data = [
            'email' => 'test@example.com',
            'addresses' => [
                [
                    'street' => 'Main Street',
                    'zip' => 12345,
                    'country' => [
                        'iso' => 'de_DE',
                    ],
                ],
            ],
        ];

        $user = new \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer();
        $user->fromArray($data, 'camelCase', true);

        self::assertSame('test@example.com', $user->getEmail());
        self::assertCount(1, $user->getAddresses());
        
        $address = $user->getAddresses()->offsetGet(0);
        self::assertInstanceOf(\PhilippHermes\TransferBundle\Tests\Data\Generated\AddressTransfer::class, $address);
        self::assertSame('Main Street', $address->getStreet());
        self::assertSame(12345, $address->getZip());
        
        $country = $address->getCountry();
        self::assertInstanceOf(\PhilippHermes\TransferBundle\Tests\Data\Generated\CountryTransfer::class, $country);
        self::assertSame('de_DE', $country->getIso());
    }

    /**
     * @return void
     */
    public function testRoundTripConversion(): void
    {
        $country = new \PhilippHermes\TransferBundle\Tests\Data\Generated\CountryTransfer();
        $country->setIso('us_US');

        $address = new \PhilippHermes\TransferBundle\Tests\Data\Generated\AddressTransfer();
        $address->setStreet('Broadway');
        $address->setZip(10001);
        $address->setCountry($country);

        $user = new \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer();
        $user->setEmail('user@example.com');
        $user->setPassword('password123');
        $user->addAddress($address);

        $array = $user->toArray('camelCase', true);

        $newUser = new \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer();
        $newUser->fromArray($array, 'camelCase', true);

        self::assertSame($user->getEmail(), $newUser->getEmail());
        self::assertSame($user->getPassword(), $newUser->getPassword());
        self::assertCount(1, $newUser->getAddresses());
        
        $newAddress = $newUser->getAddresses()->offsetGet(0);
        self::assertSame('Broadway', $newAddress->getStreet());
        self::assertSame(10001, $newAddress->getZip());
        self::assertSame('us_US', $newAddress->getCountry()->getIso());
    }

    /**
     * @return void
     */
    public function testToArrayWithCustomDateTimeFormat(): void
    {
        $country = new \PhilippHermes\TransferBundle\Tests\Data\Generated\CountryTransfer();
        $country->setIso('de_DE');
        $country->setCreatedAt(new \DateTime('2024-01-15 10:30:00'));

        $array = $country->toArray('camelCase', true, 'Y-m-d H:i:s');

        self::assertArrayHasKey('createdAt', $array);
        self::assertSame('2024-01-15 10:30:00', $array['createdAt']);
    }

    /**
     * @return void
     */
    public function testFromArrayWithCustomDateTimeFormat(): void
    {
        $data = [
            'iso' => 'de_DE',
            'createdAt' => '2024-01-15 10:30:00',
        ];

        $country = new \PhilippHermes\TransferBundle\Tests\Data\Generated\CountryTransfer();
        $country->fromArray($data, 'camelCase', true, 'Y-m-d H:i:s');

        self::assertSame('de_DE', $country->getIso());
        self::assertInstanceOf(\DateTime::class, $country->getCreatedAt());
        self::assertSame('2024-01-15 10:30:00', $country->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * @return void
     */
    public function testDateTimeFormatRoundTrip(): void
    {
        $country = new \PhilippHermes\TransferBundle\Tests\Data\Generated\CountryTransfer();
        $country->setIso('us_US');
        $country->setCreatedAt(new \DateTime('2024-12-25 15:45:30'));

        $array = $country->toArray('camelCase', true, 'Y-m-d\TH:i:s');

        $newCountry = new \PhilippHermes\TransferBundle\Tests\Data\Generated\CountryTransfer();
        $newCountry->fromArray($array, 'camelCase', true, 'Y-m-d\TH:i:s');

        self::assertSame(
            $country->getCreatedAt()->format('Y-m-d H:i:s'),
            $newCountry->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }

    /**
     * @return void
     */
    public function testPropertyConstants(): void
    {
        $UserTransfer = \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer::class;
        
        self::assertSame('email', $UserTransfer::EMAIL);
        self::assertSame('password', $UserTransfer::PASSWORD);
        self::assertSame('addresses', $UserTransfer::ADDRESSES);
    }

    /**
     * @return void
     */
    public function testFromArrayWithConstants(): void
    {
        $UserTransfer = \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer::class;
        
        $data = [
            $UserTransfer::EMAIL => 'test@example.com',
            $UserTransfer::PASSWORD => 'secret123',
        ];

        $user = new \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer();
        $user->fromArray($data);

        self::assertSame('test@example.com', $user->getEmail());
        self::assertSame('secret123', $user->getPassword());
    }
}
