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

        unlink(__DIR__ . '/../Data/Generated/Address.php');
        unlink(__DIR__ . '/../Data/Generated/User.php');
        rmdir(__DIR__ . '/../Data/Generated');
    }

    /**
     * @return void
     */
    public function testGenerate(): void
    {
        $transferCollection = $this->xmlSchemaParser->parse();

        $this->transferGenerator->generate($transferCollection);

        self::assertFileExists(__DIR__ . '/../Data/Generated/Address.php');
        self::assertFileExists(__DIR__ . '/../Data/Generated/User.php');
    }
}