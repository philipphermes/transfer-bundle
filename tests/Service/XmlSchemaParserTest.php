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

        self::assertCount(2, $transferCollection->getTransfers());

        if ($transferCollection->getTransfers()->offsetGet(0)->getName() === 'Address') {
            $transfer1 = $transferCollection->getTransfers()->offsetGet(0);
            $transfer2 = $transferCollection->getTransfers()->offsetGet(1);
        } else {
            $transfer1 = $transferCollection->getTransfers()->offsetGet(1);
            $transfer2 = $transferCollection->getTransfers()->offsetGet(0);
        }

        self::assertSame('Address', $transfer1->getName());

        if ($transfer1->getProperties()->offsetGet(0)->getName() === 'street') {
            $street =$transfer1->getProperties()->offsetGet(0);
            $zip = $transfer1->getProperties()->offsetGet(1);
        } else {
            $street =$transfer1->getProperties()->offsetGet(1);
            $zip = $transfer1->getProperties()->offsetGet(0);
        }

        self::assertSame('street', $street->getName());
        self::assertSame('string', $street->getType());
        self::assertNull($street->getDescription());
        self::assertNull($street->getSingular());
        self::assertFalse($street->getIsNullable());

        self::assertSame('zip', $zip->getName());
        self::assertSame('int', $zip->getType());
        self::assertNull($zip->getDescription());
        self::assertNull($zip->getSingular());
        self::assertFalse($zip->getIsNullable());

        self::assertSame('User', $transfer2->getName());

        self::assertSame('email', $transfer2->getProperties()->offsetGet(0)->getName());
        self::assertSame('string', $transfer2->getProperties()->offsetGet(0)->getType());
        self::assertNull($transfer2->getProperties()->offsetGet(0)->getDescription());
        self::assertNull($transfer2->getProperties()->offsetGet(0)->getSingular());
        self::assertFalse($transfer2->getProperties()->offsetGet(0)->getIsNullable());

        self::assertSame('password', $transfer2->getProperties()->offsetGet(1)->getName());
        self::assertSame('string', $transfer2->getProperties()->offsetGet(1)->getType());
        self::assertNull($transfer2->getProperties()->offsetGet(1)->getDescription());
        self::assertNull($transfer2->getProperties()->offsetGet(1)->getSingular());
        self::assertFalse($transfer2->getProperties()->offsetGet(1)->getIsNullable());

        self::assertSame('addresses', $transfer2->getProperties()->offsetGet(2)->getName());
        self::assertSame('Address[]', $transfer2->getProperties()->offsetGet(2)->getType());
        self::assertNull($transfer2->getProperties()->offsetGet(2)->getDescription());
        self::assertSame('address', $transfer2->getProperties()->offsetGet(2)->getSingular());
        self::assertTrue($transfer2->getProperties()->offsetGet(2)->getIsNullable());

        self::assertSame('roles', $transfer2->getProperties()->offsetGet(3)->getName());
        self::assertSame('string[]', $transfer2->getProperties()->offsetGet(3)->getType());
        self::assertSame('List of roles', $transfer2->getProperties()->offsetGet(3)->getDescription());
        self::assertNull($transfer2->getProperties()->offsetGet(3)->getSingular());
        self::assertFalse($transfer2->getProperties()->offsetGet(3)->getIsNullable());
    }
}