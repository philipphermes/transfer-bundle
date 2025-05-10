<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Service;

use PhilippHermes\TransferBundle\Service\Model\XmlSchemaParser;
use PHPUnit\Framework\TestCase;

class XmlSchemaParserTest extends TestCase
{
    private XmlSchemaParser $xmlSchemaParser;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->xmlSchemaParser = new XmlSchemaParser(__DIR__ . '/../Data');
    }

    /**
     * @return void
     */
    public function testParseXml(): void
    {
        $transferCollection = $this->xmlSchemaParser->parse();

        self::assertCount(3, $transferCollection->getTransfers());

        $userTransfer = null;
        $addressTransfer = null;
        $countryTransfer = null;

        foreach ($transferCollection->getTransfers() as $transfer) {
            if ($transfer->getName() === 'User') {
                $userTransfer = $transfer;
            }

            if ($transfer->getName() === 'Address') {
                $addressTransfer = $transfer;
            }

            if ($transfer->getName() === 'Country') {
                $countryTransfer = $transfer;
            }
        }

        self::assertSame('Address', $addressTransfer->getName());

        self::assertSame('zip', $addressTransfer->getProperties()->offsetGet(0)->getName());
        self::assertSame('int', $addressTransfer->getProperties()->offsetGet(0)->getType());
        self::assertNull($addressTransfer->getProperties()->offsetGet(0)->getDescription());
        self::assertNull($addressTransfer->getProperties()->offsetGet(0)->getSingular());
        self::assertFalse($addressTransfer->getProperties()->offsetGet(0)->getIsNullable());

        self::assertSame('street', $addressTransfer->getProperties()->offsetGet(1)->getName());
        self::assertSame('string', $addressTransfer->getProperties()->offsetGet(1)->getType());
        self::assertNull($addressTransfer->getProperties()->offsetGet(1)->getDescription());
        self::assertNull($addressTransfer->getProperties()->offsetGet(1)->getSingular());
        self::assertFalse($addressTransfer->getProperties()->offsetGet(1)->getIsNullable());

        self::assertSame('User', $userTransfer->getName());

        self::assertSame('email', $userTransfer->getProperties()->offsetGet(0)->getName());
        self::assertSame('string', $userTransfer->getProperties()->offsetGet(0)->getType());
        self::assertNull($userTransfer->getProperties()->offsetGet(0)->getDescription());
        self::assertNull($userTransfer->getProperties()->offsetGet(0)->getSingular());
        self::assertFalse($userTransfer->getProperties()->offsetGet(0)->getIsNullable());
        self::assertFalse($userTransfer->getProperties()->offsetGet(0)->getIsSensitive());
        self::assertTrue($userTransfer->getProperties()->offsetGet(0)->getIsIdentifier());

        self::assertSame('password', $userTransfer->getProperties()->offsetGet(1)->getName());
        self::assertSame('string', $userTransfer->getProperties()->offsetGet(1)->getType());
        self::assertNull($userTransfer->getProperties()->offsetGet(1)->getDescription());
        self::assertNull($userTransfer->getProperties()->offsetGet(1)->getSingular());
        self::assertFalse($userTransfer->getProperties()->offsetGet(1)->getIsNullable());
        self::assertFalse($userTransfer->getProperties()->offsetGet(1)->getIsSensitive());
        self::assertFalse($userTransfer->getProperties()->offsetGet(1)->getIsIdentifier());

        self::assertSame('plainPassword', $userTransfer->getProperties()->offsetGet(2)->getName());
        self::assertSame('string', $userTransfer->getProperties()->offsetGet(2)->getType());
        self::assertNull($userTransfer->getProperties()->offsetGet(2)->getDescription());
        self::assertNull($userTransfer->getProperties()->offsetGet(2)->getSingular());
        self::assertTrue($userTransfer->getProperties()->offsetGet(2)->getIsNullable());
        self::assertTrue($userTransfer->getProperties()->offsetGet(2)->getIsSensitive());
        self::assertFalse($userTransfer->getProperties()->offsetGet(2)->getIsIdentifier());

        self::assertSame('addresses', $userTransfer->getProperties()->offsetGet(3)->getName());
        self::assertSame('Address[]', $userTransfer->getProperties()->offsetGet(3)->getType());
        self::assertNull($userTransfer->getProperties()->offsetGet(3)->getDescription());
        self::assertSame('address', $userTransfer->getProperties()->offsetGet(3)->getSingular());
        self::assertTrue($userTransfer->getProperties()->offsetGet(3)->getIsNullable());
        self::assertFalse($userTransfer->getProperties()->offsetGet(3)->getIsSensitive());
        self::assertFalse($userTransfer->getProperties()->offsetGet(3)->getIsIdentifier());

        self::assertSame('Country', $countryTransfer->getName());

        self::assertSame('iso', $countryTransfer->getProperties()->offsetGet(0)->getName());
        self::assertSame('string', $countryTransfer->getProperties()->offsetGet(0)->getType());
        self::assertNull($countryTransfer->getProperties()->offsetGet(0)->getDescription());
        self::assertNull($countryTransfer->getProperties()->offsetGet(0)->getSingular());
        self::assertFalse($countryTransfer->getProperties()->offsetGet(0)->getIsNullable());
        self::assertFalse($countryTransfer->getProperties()->offsetGet(0)->getIsSensitive());
        self::assertFalse($countryTransfer->getProperties()->offsetGet(0)->getIsIdentifier());
    }
}