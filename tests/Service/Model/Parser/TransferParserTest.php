<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Service\Model\Parser;

use PhilippHermes\TransferBundle\Tests\Support\TempDirTrait;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TransferParserTest extends TestCase
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

    /**
     * Finding #1: excludes were only applied to the top-level schema dirs.
     */
    public function testExcludedSubdirectoryOfSchemaDirIsSkipped(): void
    {
        $this->writeSchema('schemas/keep.xml', '<transfer name="Keep"><property name="a" type="string"/></transfer>');
        $this->writeSchema('schemas/sub/skip.xml', '<transfer name="Skip"><property name="a" type="string"/></transfer>');

        $collection = $this->parse($this->createConfig(['schemas'], ['schemas/sub']));

        self::assertSame(['Keep'], $this->names($collection));
    }

    /**
     * Finding #2: prefix match without separator also excluded sibling directories.
     */
    public function testExcludeDoesNotMatchSiblingWithSamePrefix(): void
    {
        $this->writeSchema('schemas/foo/foo.xml', '<transfer name="Foo"><property name="a" type="string"/></transfer>');
        $this->writeSchema('schemas/foobar/foobar.xml', '<transfer name="FooBar"><property name="a" type="string"/></transfer>');

        $collection = $this->parse($this->createConfig(['schemas/*'], ['schemas/foo']));

        self::assertSame(['FooBar'], $this->names($collection));
    }

    /**
     * Finding #3: api flag of later definitions was ignored.
     */
    public function testApiFlagAndAliasAreMergedAcrossFiles(): void
    {
        $this->writeSchema('schemas/a_address.xml', '<transfer name="Address"><property name="street" type="string"/></transfer>');
        $this->writeSchema('schemas/b_address.xml', '<transfer name="Address" api="true" apiAlias="AddressResource"><property name="zip" type="int"/></transfer>');

        $collection = $this->parse($this->createConfig());
        $address = $this->transfer($collection, 'Address');

        self::assertSame([], $collection->getErrors());
        self::assertTrue($address->isApi());
        self::assertSame('AddressResource', $address->getApiAlias());
        self::assertCount(2, $address->getProperties());
    }

    public function testConflictingApiAliasesAddError(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="Address" api="true" apiAlias="One"><property name="a" type="string"/></transfer>');
        $this->writeSchema('schemas/b.xml', '<transfer name="Address" api="true" apiAlias="Two"><property name="b" type="string"/></transfer>');

        $collection = $this->parse($this->createConfig());

        self::assertCount(1, $collection->getErrors());
        self::assertStringContainsString('Address', $collection->getErrors()[0]);
    }

    /**
     * Finding #3 + low: files must be read in a deterministic (sorted) order, and a
     * conflicting duplicate property must produce a warning.
     */
    public function testFilesAreParsedInSortedOrderAndConflictingPropertyWarns(): void
    {
        // created in reverse order on purpose
        $this->writeSchema('schemas/b.xml', '<transfer name="Item"><property name="value" type="string"/></transfer>');
        $this->writeSchema('schemas/a.xml', '<transfer name="Item"><property name="value" type="int"/></transfer>');

        $collection = $this->parse($this->createConfig());
        $item = $this->transfer($collection, 'Item');

        self::assertCount(1, $item->getProperties());
        self::assertSame('int', $item->getProperties()->offsetGet(0)->getType());
        self::assertCount(1, $collection->getWarnings());
        self::assertStringContainsString('value', $collection->getWarnings()[0]);
    }

    public function testIdenticalDuplicatePropertyDoesNotWarn(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="Item"><property name="value" type="int"/></transfer>');
        $this->writeSchema('schemas/b.xml', '<transfer name="Item"><property name="value" type="int"/></transfer>');

        $collection = $this->parse($this->createConfig());

        self::assertSame([], $collection->getWarnings());
    }

    /**
     * Finding #6: xs:boolean allows "1".
     */
    public function testBooleanAttributesAcceptOne(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="Item" api="1"><property name="value" type="int" isNullable="1"/></transfer>');

        $item = $this->transfer($this->parse($this->createConfig()), 'Item');

        self::assertTrue($item->isApi());
        self::assertTrue($item->getProperties()->offsetGet(0)->isNullable());
    }

    public function testBooleanAttributesAcceptFalseAndZero(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="Item" api="0"><property name="value" type="int" isNullable="false"/></transfer>');

        $item = $this->transfer($this->parse($this->createConfig()), 'Item');

        self::assertFalse($item->isApi());
        self::assertFalse($item->getProperties()->offsetGet(0)->isNullable());
    }

    /**
     * Finding #7: malformed XML emitted PHP warnings instead of a collected error.
     */
    public function testInvalidXmlIsReportedAsErrorWithoutPhpWarning(): void
    {
        $this->writeFile('schemas/broken.xml', '<transfers><transfer');
        $this->writeSchema('schemas/valid.xml', '<transfer name="Valid"><property name="a" type="string"/></transfer>');

        $collection = $this->parse($this->createConfig());

        self::assertCount(1, $collection->getErrors());
        self::assertStringContainsString('broken.xml', $collection->getErrors()[0]);
        self::assertSame(['Valid'], $this->names($collection));
    }

    /**
     * Finding #8: names were not validated as PHP identifiers.
     */
    public function testInvalidIdentifiersAreReported(): void
    {
        $this->writeSchema('schemas/a.xml', <<<'XML'
            <transfer name="User-Profile"><property name="a" type="string"/></transfer>
            <transfer name="User">
                <property name="first-name" type="string"/>
                <property name="tags" type="string[]" singular="a tag"/>
            </transfer>
            XML);

        $errors = implode("\n", $this->parse($this->createConfig())->getErrors());

        self::assertStringContainsString('User-Profile', $errors);
        self::assertStringContainsString('first-name', $errors);
        self::assertStringContainsString('a tag', $errors);
    }

    /**
     * Finding #8: properties differing only in case crashed the generator.
     */
    public function testCaseCollidingAccessorsAreReported(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="foo" type="string"/><property name="Foo" type="int"/></transfer>');

        $errors = $this->parse($this->createConfig())->getErrors();

        self::assertCount(1, $errors);
        self::assertStringContainsString('getFoo', $errors[0]);
    }

    public function testSingularCollidingWithOtherPropertyIsReported(): void
    {
        $this->writeSchema('schemas/a.xml', <<<'XML'
            <transfer name="User">
                <property name="item" type="string"/>
                <property name="items" type="string[]" singular="item"/>
                <property name="other" type="string[]" singular="Item"/>
            </transfer>
            XML);

        $errors = implode("\n", $this->parse($this->createConfig())->getErrors());

        self::assertStringContainsString('addItem', $errors);
    }

    /**
     * Finding #9: unknown types silently became non-existent {X}Transfer classes.
     */
    public function testUnknownTypeIsReported(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="Order"><property name="price" type="Money"/></transfer>');

        $errors = $this->parse($this->createConfig())->getErrors();

        self::assertCount(1, $errors);
        self::assertStringContainsString('Money', $errors[0]);
        self::assertStringContainsString('Order', $errors[0]);
    }

    /**
     * Low: a schema dir that matches nothing was silent.
     */
    public function testMissingSchemaDirectoryAddsWarning(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="Item"><property name="a" type="string"/></transfer>');

        $collection = $this->parse($this->createConfig(['schemas', 'missing']));

        self::assertSame([], $collection->getErrors());
        self::assertSame(['Item'], $this->names($collection));
        self::assertCount(1, $collection->getWarnings());
        self::assertStringContainsString($this->tempDir . '/missing', $collection->getWarnings()[0]);
        self::assertStringContainsString('did not match any directory', $collection->getWarnings()[0]);
    }

    public function testTransferWithoutNameIsReported(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer><property name="a" type="string"/></transfer>');

        $errors = $this->parse($this->createConfig())->getErrors();

        self::assertCount(1, $errors);
        self::assertStringContainsString('a.xml', $errors[0]);
    }

    public function testPropertyWithoutNameIsReported(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property type="string"/><property name="email" type="string"/></transfer>');

        $collection = $this->parse($this->createConfig());

        self::assertCount(1, $collection->getErrors());
        self::assertStringContainsString("Missing 'name' attribute in transfer 'User'", $collection->getErrors()[0]);
        self::assertCount(1, $this->transfer($collection, 'User')->getProperties());
    }

    public function testPropertyWithoutTypeIsReported(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="password"/><property name="email" type="string"/></transfer>');

        $collection = $this->parse($this->createConfig());

        self::assertCount(1, $collection->getErrors());
        self::assertStringContainsString("Missing 'type' attribute in transfer 'User'", $collection->getErrors()[0]);
        self::assertCount(1, $this->transfer($collection, 'User')->getProperties());
    }

    public function testXmlWithoutTransfersIsReported(): void
    {
        $this->writeFile('schemas/other.xml', '<?xml version="1.0"?><config><value>1</value></config>');

        $errors = $this->parse($this->createConfig())->getErrors();

        self::assertCount(1, $errors);
        self::assertStringContainsString("Invalid XML structure in 'other.xml'", $errors[0]);
    }

    public function testUnknownAttributeProducesSchemaWarning(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string" isNulable="true"/></transfer>');

        $collection = $this->parse($this->createConfig());

        self::assertSame([], $collection->getErrors());
        self::assertSame(['User'], $this->names($collection));
        self::assertCount(1, $collection->getWarnings());
        self::assertStringContainsString("Schema validation failed for 'a.xml' (line 3)", $collection->getWarnings()[0]);
        self::assertStringContainsString('isNulable', $collection->getWarnings()[0]);
    }

    public function testValidSchemaProducesNoWarnings(): void
    {
        $this->writeSchema('schemas/a.xml', <<<'XML'
            <transfer name="User" api="true" apiAlias="UserResource">
                <property name="email" type="string" description="Mail" example="a@b.c" default="x" deprecated="true" isNullable="1"/>
                <property name="createdAt" type="DateTimeInterface"/>
            </transfer>
            XML);

        self::assertSame([], $this->parse($this->createConfig())->getWarnings());
    }

    public function testDefaultValuesAreConvertedToThePropertyType(): void
    {
        $this->writeSchema('schemas/a.xml', <<<'XML'
            <transfer name="Settings">
                <property name="name" type="string" default="none"/>
                <property name="limit" type="int" default="-10"/>
                <property name="ratio" type="float" default="0.5"/>
                <property name="enabled" type="bool" default="false"/>
                <property name="visible" type="bool" default="1"/>
                <property name="plain" type="string"/>
            </transfer>
            XML);

        $collection = $this->parse($this->createConfig());
        self::assertSame([], $collection->getErrors());

        $values = [];
        foreach ($this->transfer($collection, 'Settings')->getProperties() as $property) {
            $values[$property->getName()] = $property->hasDefaultValue() ? $property->getDefaultValue() : 'no default';
        }

        self::assertSame(
            ['name' => 'none', 'limit' => -10, 'ratio' => 0.5, 'enabled' => false, 'visible' => true, 'plain' => 'no default'],
            $values,
        );
    }

    /**
     * @return array<array{string, string, string}>
     */
    public static function invalidDefaultProvider(): array
    {
        return [
            'int' => ['int', '1.5', "invalid default '1.5' for type 'int'"],
            'float' => ['float', 'abc', "invalid default 'abc' for type 'float'"],
            'bool' => ['bool', 'yes', "invalid default 'yes' for type 'bool'"],
            'collection' => ['string[]', 'a', 'default values are only supported for string, int, float and bool'],
            'transfer' => ['Settings', 'a', 'default values are only supported for string, int, float and bool'],
        ];
    }

    #[DataProvider('invalidDefaultProvider')]
    public function testInvalidDefaultValuesAreReported(string $type, string $default, string $message): void
    {
        $this->writeSchema('schemas/a.xml', sprintf(
            '<transfer name="Settings"><property name="value" type="%s" default="%s"/></transfer>',
            $type,
            $default,
        ));

        self::assertSame(
            ["Transfer 'Settings', property 'value': " . $message],
            $this->parse($this->createConfig())->getErrors(),
        );
    }

    /**
     * @return array<string>
     */
    private function names(TransferCollectionTransfer $collection): array
    {
        $names = array_map(fn (TransferTransfer $transfer) => $transfer->getName(), $collection->getTransfers()->getArrayCopy());
        sort($names);

        return $names;
    }

    private function transfer(TransferCollectionTransfer $collection, string $name): TransferTransfer
    {
        foreach ($collection->getTransfers() as $transfer) {
            if ($transfer->getName() === $name) {
                return $transfer;
            }
        }

        self::fail(sprintf('Transfer "%s" not found', $name));
    }
}
