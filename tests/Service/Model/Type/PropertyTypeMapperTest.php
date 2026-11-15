<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Service\Model\Type;

use InvalidArgumentException;
use PhilippHermes\TransferBundle\Service\Model\Type\PropertyTypeMapper;
use PhilippHermes\TransferBundle\Tests\Support\Fixtures\Identifiable;
use PhilippHermes\TransferBundle\Tests\Support\Fixtures\Status;
use PhilippHermes\TransferBundle\Tests\Support\Fixtures\ValueObject;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PropertyTypeMapperTest extends TestCase
{
    private const string NAMESPACE = 'App\\Gen';

    private PropertyTypeMapper $mapper;

    protected function setUp(): void
    {
        $collection = (new TransferCollectionTransfer())
            ->addTransfer((new TransferTransfer())->setName('Address'))
            ->addTransfer((new TransferTransfer())->setName('BankTransfer'));

        $this->mapper = new PropertyTypeMapper();
        $this->mapper->setDefinedTransfers($collection);
    }

    public function testDefinedTransferIsSuffixedAndNamespaced(): void
    {
        $property = $this->map('Address');

        self::assertSame('App\\Gen\\AddressTransfer', $property->getType());
        self::assertTrue($property->isTransfer());
        self::assertFalse($property->isCollection());
    }

    public function testDefinedTransferCollection(): void
    {
        $property = $this->map('Address[]');

        self::assertSame('ArrayObject', $property->getType());
        self::assertSame('App\\Gen\\AddressTransfer', $property->getSingularType());
        self::assertFalse($property->isTransfer());
        self::assertTrue($property->isSingularTransfer());
        self::assertTrue($property->isCollection());
    }

    public function testScalarCollectionIsArray(): void
    {
        $property = $this->map('string[]');

        self::assertSame('array', $property->getType());
        self::assertSame('string', $property->getSingularType());
        self::assertFalse($property->isSingularTransfer());
        self::assertTrue($property->isCollection());
    }

    /**
     * Code quality: a transfer whose name contains "Transfer" was misclassified by str_contains().
     */
    public function testTransferNameContainingTransferIsResolved(): void
    {
        $property = $this->map('BankTransfer');

        self::assertSame('App\\Gen\\BankTransferTransfer', $property->getType());
        self::assertTrue($property->isTransfer());
    }

    /**
     * Finding #10: existing userland classes/enums/interfaces were suffixed with "Transfer".
     */
    #[DataProvider('existingClassProvider')]
    public function testExistingClassIsUsedAsFqcn(string $type, string $expected): void
    {
        $property = $this->map($type);

        self::assertSame($expected, $property->getType());
        self::assertFalse($property->isTransfer());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function existingClassProvider(): array
    {
        return [
            'internal class' => ['DateTime', 'DateTime'],
            'leading backslash' => ['\\DateTimeImmutable', 'DateTimeImmutable'],
            'userland class' => [ValueObject::class, ValueObject::class],
            'userland class with leading backslash' => ['\\' . ValueObject::class, ValueObject::class],
            'enum' => [Status::class, Status::class],
            'interface' => [Identifiable::class, Identifiable::class],
            'class containing Transfer' => [PropertyTransfer::class, PropertyTransfer::class],
        ];
    }

    public function testExistingClassCollection(): void
    {
        $property = $this->map(ValueObject::class . '[]');

        self::assertSame('ArrayObject', $property->getType());
        self::assertSame(ValueObject::class, $property->getSingularType());
        self::assertFalse($property->isSingularTransfer());
    }

    /**
     * Finding #9: unknown types must not silently become {X}Transfer.
     */
    public function testUnknownTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Money');

        $this->map('Money');
    }

    public function testUnknownCollectionTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->map('Money[]');
    }

    private function map(string $type): PropertyTransfer
    {
        $config = (new GeneratorConfigTransfer())->setNamespace(self::NAMESPACE);

        return $this->mapper->addTypes($config, (new PropertyTransfer())->setName('prop'), $type);
    }
}
