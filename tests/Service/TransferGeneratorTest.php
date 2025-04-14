<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Service;

use PhilippHermes\TransferBundle\Service\TransferGenerator;
use PhilippHermes\TransferBundle\Service\XmlSchemaParser;
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
    }
}