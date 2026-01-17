<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Data\Entity;

use PhilippHermes\TransferBundle\Entity\EntityArrayConvertibleTrait;

class TestOrderItem
{
    use EntityArrayConvertibleTrait;

    private int $id;
    private string $productName;
    private int $quantity;
    private float $price;
    private ?TestOrder $order = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): self
    {
        $this->productName = $productName;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getOrder(): ?TestOrder
    {
        return $this->order;
    }

    public function setOrder(?TestOrder $order): self
    {
        $this->order = $order;
        return $this;
    }
}
