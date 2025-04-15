<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Service;

use PhilippHermes\TransferBundle\Service\Model\TransferGenerator;
use PhilippHermes\TransferBundle\Service\Model\XmlSchemaParser;
use PHPUnit\Framework\TestCase;

class TransferGeneratorTest extends TestCase
{
    private XmlSchemaParser $xmlSchemaParser;

    private TransferGenerator $transferGenerator;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->xmlSchemaParser = new XmlSchemaParser(__DIR__ . '/../Data');
        $this->transferGenerator = new TransferGenerator(
            'PhilippHermes\TransferBundle\Tests\Data\Generated',
            __DIR__ . '/../Data/Generated'
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unlink(__DIR__ . '/../Data/Generated/AddressTransfer.php');
        unlink(__DIR__ . '/../Data/Generated/UserTransfer.php');
        rmdir(__DIR__ . '/../Data/Generated');
    }

    /**
     * @return void
     */
    public function testGenerate(): void
    {
        $transferCollection = $this->xmlSchemaParser->parse();

        foreach ($transferCollection->getTransfers() as $transfer) {
            $this->transferGenerator->generateTransfer($transfer);
        }

        self::assertFileExists(__DIR__ . '/../Data/Generated/AddressTransfer.php');
        self::assertFileExists(__DIR__ . '/../Data/Generated/UserTransfer.php');

        $user = new \PhilippHermes\TransferBundle\Tests\Data\Generated\UserTransfer();
        $address = new \PhilippHermes\TransferBundle\Tests\Data\Generated\AddressTransfer();

        $address->setStreet('test');
        self::assertSame('test', $address->getStreet());

        $address->setZip(123);
        self::assertSame(123, $address->getZip());

        $user->setEmail('test@example.com');
        self::assertSame('test@example.com', $user->getEmail());

        $user->setPassword('password');
        self::assertSame('password', $user->getPassword());

        $user->setAddresses(new \ArrayObject([$address]));
        self::assertSame('test', $user->getAddresses()->offsetGet(0)->getStreet());
        $user->addAddress($address);
        self::assertCount(2, $user->getAddresses());

        $user->setRoles(['ROLE_USER']);
        self::assertSame(['ROLE_USER'], $user->getRoles());

        $user->addRoles('ROLE_ADMIN');
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }
}