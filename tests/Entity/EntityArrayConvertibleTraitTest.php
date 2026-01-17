<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Entity;

use DateTime;
use DateTimeInterface;
use PhilippHermes\TransferBundle\Tests\Data\Entity\TestCustomer;
use PhilippHermes\TransferBundle\Tests\Data\Entity\TestOrder;
use PhilippHermes\TransferBundle\Tests\Data\Entity\TestOrderItem;
use PHPUnit\Framework\TestCase;

class EntityArrayConvertibleTraitTest extends TestCase
{
    /**
     * @return void
     */
    public function testToArrayBasic(): void
    {
        $customer = new TestCustomer();
        $customer->setId(1);
        $customer->setEmail('john@example.com');
        $customer->setName('John Doe');

        $array = $customer->toArray('camelCase', false);

        self::assertArrayHasKey('id', $array);
        self::assertArrayHasKey('email', $array);
        self::assertArrayHasKey('name', $array);
        self::assertSame(1, $array['id']);
        self::assertSame('john@example.com', $array['email']);
        self::assertSame('John Doe', $array['name']);
    }

    /**
     * @return void
     */
    public function testToArraySnakeCase(): void
    {
        $item = new TestOrderItem();
        $item->setId(1);
        $item->setProductName('Widget');
        $item->setQuantity(5);
        $item->setPrice(19.99);

        $array = $item->toArray('snake_case', false);

        self::assertArrayHasKey('product_name', $array);
        self::assertSame('Widget', $array['product_name']);
    }

    /**
     * @return void
     */
    public function testToArrayWithDateTime(): void
    {
        $order = new TestOrder();
        $order->setId(1);
        $order->setOrderNumber('ORD-001');
        $order->setTotal(99.99);
        $order->setCreatedAt(new DateTime('2024-01-15 10:30:00'));

        $array = $order->toArray('camelCase', false, 'Y-m-d H:i:s');

        self::assertArrayHasKey('createdAt', $array);
        self::assertSame('2024-01-15 10:30:00', $array['createdAt']);
    }

    /**
     * @return void
     */
    public function testToArrayRecursive(): void
    {
        $customer = new TestCustomer();
        $customer->setId(1);
        $customer->setEmail('john@example.com');
        $customer->setName('John Doe');

        $order = new TestOrder();
        $order->setId(100);
        $order->setOrderNumber('ORD-001');
        $order->setTotal(99.99);
        $order->setCreatedAt(new DateTime('2024-01-15 10:30:00'));
        $order->setCustomer($customer);

        $array = $order->toArray('camelCase', true, DateTimeInterface::ATOM);

        self::assertArrayHasKey('customer', $array);
        self::assertIsArray($array['customer']);
        self::assertArrayHasKey('email', $array['customer']);
        self::assertSame('john@example.com', $array['customer']['email']);
    }

    /**
     * @return void
     */
    public function testToArrayWithCollection(): void
    {
        $customer = new TestCustomer();
        $customer->setId(1);
        $customer->setEmail('john@example.com');
        $customer->setName('John Doe');

        $order1 = new TestOrder();
        $order1->setId(100);
        $order1->setOrderNumber('ORD-001');
        $order1->setTotal(99.99);
        $order1->setCreatedAt(new DateTime('2024-01-15'));

        $order2 = new TestOrder();
        $order2->setId(101);
        $order2->setOrderNumber('ORD-002');
        $order2->setTotal(149.99);
        $order2->setCreatedAt(new DateTime('2024-01-16'));

        $customer->addOrder($order1);
        $customer->addOrder($order2);

        $array = $customer->toArray('camelCase', true, 'Y-m-d', 5);

        self::assertArrayHasKey('orders', $array);
        self::assertIsArray($array['orders']);
        self::assertCount(2, $array['orders']);
        self::assertSame('ORD-001', $array['orders'][0]['orderNumber']);
        self::assertSame('ORD-002', $array['orders'][1]['orderNumber']);
    }

    /**
     * @return void
     */
    public function testToArrayCircularReference(): void
    {
        $customer = new TestCustomer();
        $customer->setId(1);
        $customer->setEmail('john@example.com');
        $customer->setName('John Doe');

        $order = new TestOrder();
        $order->setId(100);
        $order->setOrderNumber('ORD-001');
        $order->setTotal(99.99);
        $order->setCreatedAt(new DateTime('2024-01-15'));

        $customer->addOrder($order);

        $array = $customer->toArray('camelCase', true, DateTimeInterface::ATOM, 10);

        self::assertArrayHasKey('orders', $array);
        self::assertArrayHasKey('customer', $array['orders'][0]);
        self::assertArrayHasKey('_circular_reference', $array['orders'][0]['customer']);
        self::assertTrue($array['orders'][0]['customer']['_circular_reference']);
    }

    /**
     * @return void
     */
    public function testToArrayMaxDepth(): void
    {
        $customer = new TestCustomer();
        $customer->setId(1);
        $customer->setEmail('john@example.com');
        $customer->setName('John Doe');

        $order = new TestOrder();
        $order->setId(100);
        $order->setOrderNumber('ORD-001');
        $order->setTotal(99.99);
        $order->setCreatedAt(new DateTime('2024-01-15'));
        $order->setCustomer($customer);

        $array = $order->toArray('camelCase', true, DateTimeInterface::ATOM, 1);

        self::assertArrayHasKey('customer', $array);
        self::assertEmpty($array['customer']);
    }

    /**
     * @return void
     */
    public function testToArrayWithCollectionItems(): void
    {
        $order = new TestOrder();
        $order->setId(100);
        $order->setOrderNumber('ORD-001');
        $order->setTotal(99.99);
        $order->setCreatedAt(new DateTime('2024-01-15'));

        $item1 = new TestOrderItem();
        $item1->setId(1);
        $item1->setProductName('Widget');
        $item1->setQuantity(2);
        $item1->setPrice(29.99);

        $item2 = new TestOrderItem();
        $item2->setId(2);
        $item2->setProductName('Gadget');
        $item2->setQuantity(1);
        $item2->setPrice(39.99);

        $order->addItem($item1);
        $order->addItem($item2);

        $array = $order->toArray('camelCase', true, DateTimeInterface::ATOM, 5);

        self::assertArrayHasKey('items', $array);
        self::assertIsArray($array['items']);
        self::assertCount(2, $array['items']);
        self::assertSame('Widget', $array['items'][0]['productName']);
        self::assertSame('Gadget', $array['items'][1]['productName']);
    }

    /**
     * @return void
     */
    public function testFromArrayBasic(): void
    {
        $data = [
            'id' => 1,
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ];

        $customer = new TestCustomer();
        $customer->fromArray($data);

        self::assertSame(1, $customer->getId());
        self::assertSame('jane@example.com', $customer->getEmail());
        self::assertSame('Jane Doe', $customer->getName());
    }

    /**
     * @return void
     */
    public function testFromArraySnakeCase(): void
    {
        $data = [
            'id' => 1,
            'product_name' => 'Widget',
            'quantity' => 5,
            'price' => 19.99,
        ];

        $item = new TestOrderItem();
        $item->fromArray($data, 'snake_case');

        self::assertSame('Widget', $item->getProductName());
        self::assertSame(5, $item->getQuantity());
    }

    /**
     * @return void
     */
    public function testFromArrayWithDateTime(): void
    {
        $data = [
            'id' => 100,
            'orderNumber' => 'ORD-001',
            'total' => 99.99,
            'createdAt' => '2024-01-15 10:30:00',
        ];

        $order = new TestOrder();
        $order->fromArray($data, 'camelCase', true, 'Y-m-d H:i:s');

        self::assertSame('ORD-001', $order->getOrderNumber());
        self::assertInstanceOf(DateTime::class, $order->getCreatedAt());
        self::assertSame('2024-01-15 10:30:00', $order->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * @return void
     */
    public function testRoundTripConversion(): void
    {
        $customer = new TestCustomer();
        $customer->setId(1);
        $customer->setEmail('test@example.com');
        $customer->setName('Test User');

        $order = new TestOrder();
        $order->setId(100);
        $order->setOrderNumber('ORD-100');
        $order->setTotal(199.99);
        $order->setCreatedAt(new DateTime('2024-06-15 14:30:00'));
        $order->setCustomer($customer);

        $array = $order->toArray('camelCase', true, 'Y-m-d H:i:s', 5);

        $newOrder = new TestOrder();
        $newOrder->fromArray($array, 'camelCase', true, 'Y-m-d H:i:s');

        self::assertSame($order->getOrderNumber(), $newOrder->getOrderNumber());
        self::assertSame($order->getTotal(), $newOrder->getTotal());
        self::assertSame(
            $order->getCreatedAt()->format('Y-m-d H:i:s'),
            $newOrder->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }
}
