<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Service\Model\Generator;

use ArrayObject;
use DateTime;
use DateTimeImmutable;
use OpenApi\Attributes as OA;
use PhilippHermes\TransferBundle\Service\TransferService;
use PhilippHermes\TransferBundle\Service\TransferServiceFactory;
use PhilippHermes\TransferBundle\Tests\Support\Fixtures\Other\ValueObject as OtherValueObject;
use PhilippHermes\TransferBundle\Tests\Support\Fixtures\Size;
use PhilippHermes\TransferBundle\Tests\Support\Fixtures\Status;
use PhilippHermes\TransferBundle\Tests\Support\Fixtures\ValueObject;
use PhilippHermes\TransferBundle\Tests\Support\TempDirTrait;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PHPUnit\Framework\TestCase;
use ReflectionClassConstant;
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

    public function testDefaultValuesAreInitialized(): void
    {
        $this->writeSchema('schemas/a.xml', <<<'XML'
            <transfer name="Settings" api="true">
                <property name="limit" type="int" default="10"/>
                <property name="label" type="string" default="none" isNullable="true"/>
            </transfer>
            XML);

        $ns = $this->generateAndLoad($this->createConfig());
        $class = $ns . '\\SettingsTransfer';
        $settings = new $class();

        self::assertTrue($settings->hasLimit());
        self::assertSame(10, $settings->getLimit());
        self::assertSame('none', $settings->getLabel());
        self::assertSame(10, $this->openApiArguments($class, 'limit')['default']);
    }

    public function testOpenApiAttributesForEnumsExamplesAndDeprecation(): void
    {
        $this->writeSchema('schemas/a.xml', sprintf(
            <<<'XML'
                <transfer name="Shirt" api="true">
                    <property name="status" type="%1$s"/>
                    <property name="statuses" type="%1$s[]" singular="status"/>
                    <property name="size" type="%2$s"/>
                    <property name="name" type="string" example="Basic tee"/>
                    <property name="legacy" type="string[]" deprecated="true"/>
                </transfer>
                XML,
            Status::class,
            Size::class,
        ));

        $ns = $this->generateAndLoad($this->createConfig());
        $class = $ns . '\\ShirtTransfer';

        $status = $this->openApiArguments($class, 'status');
        self::assertSame('string', $status['type']);
        self::assertSame(['active'], $status['enum']);

        $statuses = $this->openApiArguments($class, 'statuses');
        self::assertInstanceOf(OA\Items::class, $statuses['items']);
        self::assertSame(['active'], $statuses['items']->enum);

        self::assertSame(['Small', 'Large'], $this->openApiArguments($class, 'size')['enum']);
        self::assertSame('Basic tee', $this->openApiArguments($class, 'name')['example']);
        self::assertTrue($this->openApiArguments($class, 'legacy')['deprecated']);

        self::assertStringContainsString('@deprecated', (string)(new ReflectionProperty($class, 'legacy'))->getDocComment());
        foreach (['getLegacy', 'hasLegacy', 'setLegacy', 'addLegacy'] as $method) {
            self::assertStringContainsString('@deprecated', (string)(new ReflectionMethod($class, $method))->getDocComment(), $method);
        }
        self::assertStringNotContainsString('@deprecated', (string)(new ReflectionMethod($class, 'getName'))->getDocComment());
    }

    public function testToArrayAndFromArrayRoundTrip(): void
    {
        $ns = $this->generateCatalog();
        $catalogClass = $ns . '\\CatalogTransfer';
        $itemClass = $ns . '\\ItemTransfer';

        $catalog = (new $catalogClass())
            ->setName('Summer')
            ->setMain((new $itemClass())->setName('Shirt'))
            ->addItem((new $itemClass())->setName('Hat'))
            ->setTags(['a', 'b'])
            ->setCreatedAt(new DateTime('2026-01-02T03:04:05+00:00'))
            ->setPublishedAt(new DateTimeImmutable('2026-02-03T04:05:06+00:00'))
            ->setStatus(Status::Active)
            ->addStatus(Status::Active)
            ->setSize(Size::Large)
            ->addDate(new DateTime('2026-03-04T05:06:07+00:00'));

        $expected = [
            'name' => 'Summer',
            'note' => null,
            'main' => ['name' => 'Shirt'],
            'items' => [['name' => 'Hat']],
            'tags' => ['a', 'b'],
            'createdAt' => '2026-01-02T03:04:05+00:00',
            'publishedAt' => '2026-02-03T04:05:06+00:00',
            'status' => 'active',
            'statuses' => ['active'],
            'size' => 'Large',
            'dates' => ['2026-03-04T05:06:07+00:00'],
            'values' => [],
        ];

        self::assertSame($expected, $catalog->toArray());

        $copy = $catalogClass::createFromArray($expected);

        self::assertInstanceOf($catalogClass, $copy);
        self::assertSame($expected, $copy->toArray());
        self::assertInstanceOf($itemClass, $copy->getItems()[0]);
        self::assertInstanceOf(DateTime::class, $copy->getCreatedAt());
        self::assertInstanceOf(DateTimeImmutable::class, $copy->getPublishedAt());
        self::assertSame(Status::Active, $copy->getStatuses()[0]);
        self::assertSame(Size::Large, $copy->getSize());
    }

    public function testToArrayOfEmptyTransferAndFromArrayWithMissingAndUnknownKeys(): void
    {
        $ns = $this->generateCatalog();
        $catalogClass = $ns . '\\CatalogTransfer';

        $empty = (new $catalogClass())->toArray();
        self::assertNull($empty['name']);
        self::assertNull($empty['main']);
        self::assertSame([], $empty['items']);

        $catalog = $catalogClass::createFromArray(['name' => 'Winter', 'unknown' => 1]);
        self::assertSame('Winter', $catalog->getName());
        self::assertFalse($catalog->hasMain());

        $main = new ($ns . '\\ItemTransfer')();
        self::assertSame($main, $catalogClass::createFromArray(['main' => $main])->getMain());
    }

    public function testPropertyNameConstantsAreGeneratedAndUsedAsArrayKeys(): void
    {
        $ns = $this->generateCatalog();
        $catalogClass = $ns . '\\CatalogTransfer';

        self::assertSame('name', constant($catalogClass . '::NAME'));
        self::assertSame('createdAt', constant($catalogClass . '::CREATED_AT'));
        self::assertSame('string', (string)(new ReflectionClassConstant($catalogClass, 'CREATED_AT'))->getType());

        $catalog = $catalogClass::createFromArray([constant($catalogClass . '::NAME') => 'Winter']);
        self::assertSame('Winter', $catalog->toArray()[constant($catalogClass . '::NAME')]);

        $source = $this->generatedFile($this->createConfig(), 'Catalog');
        self::assertStringContainsString("public const string CREATED_AT = 'createdAt';", $source);
        self::assertStringContainsString('self::CREATED_AT => ', $source);
        self::assertStringContainsString('array_key_exists(self::CREATED_AT, $data)', $source);
    }

    public function testFromArrayPopulatesExistingInstance(): void
    {
        $ns = $this->generateCatalog();
        $catalogClass = $ns . '\\CatalogTransfer';

        $catalog = (new $catalogClass())->setName('Summer')->setNote('keep');

        $result = $catalog->fromArray(['name' => 'Winter', 'items' => [['name' => 'Hat']], 'unknown' => 1]);

        self::assertSame($catalog, $result);
        self::assertSame('Winter', $catalog->getName());
        self::assertSame('keep', $catalog->getNote());
        self::assertInstanceOf($ns . '\\ItemTransfer', $catalog->getItems()[0]);
        self::assertSame('Hat', $catalog->getItems()[0]->getName());
    }

    public function testCloneIsDeep(): void
    {
        $ns = $this->generateCatalog();
        $catalogClass = $ns . '\\CatalogTransfer';
        $itemClass = $ns . '\\ItemTransfer';
        $value = new ValueObject();

        $original = (new $catalogClass())
            ->setMain((new $itemClass())->setName('Shirt'))
            ->addItem((new $itemClass())->setName('Hat'))
            ->setCreatedAt(new DateTime('2026-01-01'))
            ->addStatus(Status::Active)
            ->addValue($value);

        $clone = clone $original;
        $clone->getMain()->setName('changed');
        $clone->getItems()[0]->setName('changed');
        $clone->addItem(new $itemClass());
        $clone->getCreatedAt()->modify('+1 day');

        self::assertSame('Shirt', $original->getMain()->getName());
        self::assertSame('Hat', $original->getItems()[0]->getName());
        self::assertCount(1, $original->getItems());
        self::assertSame('2026-01-01', $original->getCreatedAt()->format('Y-m-d'));
        self::assertSame(Status::Active, $clone->getStatuses()[0]);
        // userland objects are not cloned, only the collection
        self::assertSame($value, $clone->getValues()[0]);
    }

    public function testCloneIsOnlyGeneratedWhenNeeded(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string"/><property name="tags" type="string[]"/></transfer>');

        $ns = $this->generateAndLoad($this->createConfig());

        self::assertFalse(method_exists($ns . '\\UserTransfer', '__clone'));
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

    public function testUnchangedFilesAreNotRewritten(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string"/></transfer>'
            . '<transfer name="Role"><property name="name" type="string"/></transfer>');
        $config = $this->createConfig();
        $service = new TransferService(new TransferServiceFactory());
        $collection = $service->parse($config);

        $paths = $service->generate($config, $collection, fn () => null);
        self::assertCount(2, $paths);

        $user = $config->getOutputDirectory() . '/UserTransfer.php';
        $role = $config->getOutputDirectory() . '/RoleTransfer.php';
        touch($user, 1000);
        touch($role, 1000);
        file_put_contents($role, 'outdated');
        clearstatcache();

        $progress = 0;
        self::assertSame($paths, $service->generate($config, $collection, function () use (&$progress) {
            $progress++;
        }));
        clearstatcache();

        self::assertSame(2, $progress);
        self::assertSame(1000, filemtime($user));
        self::assertStringContainsString('class RoleTransfer', (string)file_get_contents($role));
    }

    public function testRenderDoesNotWriteAnything(): void
    {
        $this->writeSchema('schemas/a.xml', '<transfer name="User"><property name="email" type="string"/></transfer>');
        $config = $this->createConfig();
        $service = new TransferService(new TransferServiceFactory());

        $files = $service->render($config, $service->parse($config));

        self::assertSame([$config->getOutputDirectory() . '/UserTransfer.php'], array_keys($files));
        self::assertStringContainsString('class UserTransfer', $files[$config->getOutputDirectory() . '/UserTransfer.php']);
        self::assertDirectoryDoesNotExist($config->getOutputDirectory());
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

    private function generateCatalog(): string
    {
        $this->writeSchema('schemas/catalog.xml', sprintf(
            <<<'XML'
                <transfer name="Catalog">
                    <property name="name" type="string"/>
                    <property name="note" type="string" isNullable="true"/>
                    <property name="main" type="Item"/>
                    <property name="items" type="Item[]" singular="item"/>
                    <property name="tags" type="string[]" singular="tag"/>
                    <property name="createdAt" type="DateTime"/>
                    <property name="publishedAt" type="DateTimeInterface"/>
                    <property name="status" type="%1$s"/>
                    <property name="statuses" type="%1$s[]" singular="status"/>
                    <property name="size" type="%2$s"/>
                    <property name="dates" type="DateTime[]" singular="date"/>
                    <property name="values" type="%3$s[]" singular="value"/>
                </transfer>
                <transfer name="Item">
                    <property name="name" type="string"/>
                </transfer>
                XML,
            Status::class,
            Size::class,
            ValueObject::class,
        ));

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
