<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Service\Model\Generator;

use ArrayObject;
use OpenApi\Attributes as OA;
use PhilippHermes\TransferBundle\Tests\Support\Fixtures\Other\ValueObject as OtherValueObject;
use PhilippHermes\TransferBundle\Tests\Support\Fixtures\Status;
use PhilippHermes\TransferBundle\Tests\Support\Fixtures\ValueObject;
use PhilippHermes\TransferBundle\Tests\Support\TempDirTrait;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use RuntimeException;

class GeneratorTest extends TestCase
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
     * Finding #11: getters of nullable collections always return a collection.
     */
    public function testNullableCollectionGetterAlwaysReturnsCollection(): void
    {
        $ns = $this->generateOrder();
        $class = $ns . '\\OrderTransfer';
        $order = new $class();

        self::assertInstanceOf(ArrayObject::class, $order->getItems());
        self::assertCount(0, $order->getItems());
        self::assertSame([], $order->getTags());

        $order->setItems(null);
        $order->setTags(null);

        self::assertInstanceOf(ArrayObject::class, $order->getItems());
        self::assertCount(0, $order->getItems());
        self::assertSame([], $order->getTags());

        $this->assertReturnTypeNotNullable($class, 'getItems');
        $this->assertReturnTypeNotNullable($class, 'getTags');
    }

    /**
     * Finding #11: adders must not accept null items.
     */
    public function testAdderDoesNotAcceptNullItems(): void
    {
        $ns = $this->generateOrder();
        $class = $ns . '\\OrderTransfer';

        foreach (['addItem', 'addTag'] as $adder) {
            $type = (new ReflectionMethod($class, $adder))->getParameters()[0]->getType();
            self::assertInstanceOf(ReflectionNamedType::class, $type);
            self::assertFalse($type->allowsNull(), $adder . ' must not accept null');
        }

        $order = new $class();
        $itemClass = $ns . '\\ItemTransfer';
        $order->addItem(new $itemClass());
        $order->addTag('a');

        self::assertCount(1, $order->getItems());
        self::assertSame(['a'], $order->getTags());
    }

    /**
     * Finding #11: setter docblocks of nullable properties must contain |null.
     */
    public function testNullableSetterDocblockContainsNull(): void
    {
        $ns = $this->generateOrder();
        $class = $ns . '\\OrderTransfer';

        foreach (['setItems', 'setTags', 'setNote'] as $setter) {
            self::assertStringContainsString('|null', (string)(new ReflectionMethod($class, $setter))->getDocComment(), $setter);
        }

        self::assertStringNotContainsString('|null', (string)(new ReflectionMethod($class, 'setId'))->getDocComment());
    }

    /**
     * Finding #12: has<Prop>() helpers.
     */
    public function testHasMethodsReportInitializedState(): void
    {
        $ns = $this->generateOrder();
        $class = $ns . '\\OrderTransfer';
        $order = new $class();

        self::assertFalse($order->hasId());
        self::assertFalse($order->hasNote());

        $order->setId(1);
        $order->setNote('note');

        self::assertTrue($order->hasId());
        self::assertTrue($order->hasNote());

        $order->setNote(null);
        self::assertFalse($order->hasNote());
    }

    /**
     * Finding #10: existing userland types are used as-is in generated code.
     */
    public function testExistingClassTypesAreUsedAsIs(): void
    {
        $this->writeSchema('schemas/a.xml', sprintf(
            '<transfer name="Holder"><property name="value" type="%s"/><property name="status" type="%s"/><property name="values" type="%s[]"/></transfer>',
            ValueObject::class,
            Status::class,
            ValueObject::class,
        ));

        $ns = $this->generateAndLoad($this->createConfig());
        $class = $ns . '\\HolderTransfer';
        $holder = new $class();

        $holder->setValue(new ValueObject());
        $holder->setStatus(Status::Active);
        $holder->addValues(new ValueObject());

        self::assertSame(Status::Active, $holder->getStatus());
        self::assertCount(1, $holder->getValues());

        $type = (new ReflectionMethod($class, 'getValue'))->getReturnType();
        self::assertInstanceOf(ReflectionNamedType::class, $type);
        self::assertSame(ValueObject::class, $type->getName());
    }

    /**
     * Low: OpenAPI type for mixed and format for DateTime collections.
     */
    public function testOpenApiAttributesForMixedAndDateTimeCollections(): void
    {
        $this->writeSchema('schemas/a.xml', <<<'XML'
            <transfer name="Event" api="true">
                <property name="payload" type="mixed"/>
                <property name="dates" type="DateTime[]"/>
                <property name="items" type="Item[]" isNullable="true"/>
            </transfer>
            <transfer name="Item" api="true"><property name="a" type="string"/></transfer>
            XML);

        $ns = $this->generateAndLoad($this->createConfig());
        $class = $ns . '\\EventTransfer';

        $payload = $this->openApiArguments($class, 'payload');
        self::assertArrayNotHasKey('type', $payload);

        $dates = $this->openApiArguments($class, 'dates');
        self::assertSame('array', $dates['type']);
        self::assertInstanceOf(OA\Items::class, $dates['items']);
        self::assertSame('date-time', $dates['items']->format);

        // the getter never returns null, so the schema must not claim it
        $items = $this->openApiArguments($class, 'items');
        self::assertArrayNotHasKey('nullable', $items);
    }

    public function testOpenApiAttributesForScalarTypes(): void
    {
        $this->writeSchema('schemas/a.xml', <<<'XML'
            <transfer name="Price" api="true">
                <property name="amount" type="float" description="The gross amount"/>
                <property name="isNet" type="bool"/>
                <property name="validFrom" type="DateTime"/>
                <property name="currency" type="string" isNullable="true"/>
            </transfer>
            XML);

        $ns = $this->generateAndLoad($this->createConfig());
        $class = $ns . '\\PriceTransfer';

        $amount = $this->openApiArguments($class, 'amount');
        self::assertSame('number', $amount['type']);
        self::assertSame('The gross amount', $amount['description']);

        self::assertSame('boolean', $this->openApiArguments($class, 'isNet')['type']);

        $validFrom = $this->openApiArguments($class, 'validFrom');
        self::assertSame('string', $validFrom['type']);
        self::assertSame('date-time', $validFrom['format']);

        $currency = $this->openApiArguments($class, 'currency');
        self::assertTrue($currency['nullable']);
        self::assertArrayNotHasKey('description', $currency);
    }

    public function testConflictingShortClassNamesAreNotImported(): void
    {
        $this->writeSchema('schemas/a.xml', sprintf(
            '<transfer name="Holder"><property name="first" type="%s"/><property name="second" type="%s"/></transfer>'
            . '<transfer name="Property"><property name="definition" type="%s"/></transfer>',
            ValueObject::class,
            OtherValueObject::class,
            PropertyTransfer::class,
        ));

        $ns = $this->generateAndLoad($this->createConfig());

        $this->assertReturnType($ns . '\\HolderTransfer', 'getFirst', ValueObject::class);
        $this->assertReturnType($ns . '\\HolderTransfer', 'getSecond', OtherValueObject::class);
        $this->assertReturnType($ns . '\\PropertyTransfer', 'getDefinition', PropertyTransfer::class);
    }

    public function testThrowsWhenOutputDirectoryCannotBeCreated(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string"/></transfer>');
        $this->writeFile('output', 'not a directory');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not create output directory');

        $this->generateAndLoad($this->createConfig());
    }

    public function testThrowsWhenFileCannotBeWritten(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string"/></transfer>');
        mkdir($this->tempDir . '/output/UserTransfer.php', 0777, true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not write');

        $this->generateAndLoad($this->createConfig());
    }

    private function assertReturnType(string $class, string $method, string $expected): void
    {
        $type = (new ReflectionMethod($class, $method))->getReturnType();

        self::assertInstanceOf(ReflectionNamedType::class, $type);
        self::assertSame($expected, $type->getName());
    }

    private function generateOrder(): string
    {
        $this->writeSchema('schemas/order.xml', <<<'XML'
            <transfer name="Order">
                <property name="id" type="int"/>
                <property name="note" type="string" isNullable="true"/>
                <property name="items" type="Item[]" singular="item" isNullable="true"/>
                <property name="tags" type="string[]" singular="tag" isNullable="true"/>
            </transfer>
            <transfer name="Item">
                <property name="name" type="string"/>
            </transfer>
            XML);

        return $this->generateAndLoad($this->createConfig());
    }

    private function assertReturnTypeNotNullable(string $class, string $method): void
    {
        $type = (new ReflectionMethod($class, $method))->getReturnType();

        self::assertInstanceOf(ReflectionNamedType::class, $type);
        self::assertFalse($type->allowsNull(), $method . ' must not be nullable');
    }

    /**
     * @return array<string, mixed>
     */
    private function openApiArguments(string $class, string $property): array
    {
        $attributes = (new ReflectionProperty($class, $property))->getAttributes(OA\Property::class);
        self::assertCount(1, $attributes);

        return $attributes[0]->getArguments();
    }
}
