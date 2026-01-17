<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Tests\Data\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PhilippHermes\TransferBundle\Entity\EntityArrayConvertibleTrait;

class TestOrder
{
    use EntityArrayConvertibleTrait;

    private int $id;
    private string $orderNumber;
    private float $total;
    private DateTime $createdAt;
    private ?TestCustomer $customer = null;
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal(float $total): self
    {
        $this->total = $total;
        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCustomer(): ?TestCustomer
    {
        return $this->customer;
    }

    public function setCustomer(?TestCustomer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(TestOrderItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }
        return $this;
    }
}
