<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Transfer;

use ArrayObject;

class TransferTransfer
{
    protected string $name;

    /**
     * @var ArrayObject<array-key, PropertyTransfer>
     */
    protected ArrayObject $properties;

    protected bool $isApi = false;

    protected ?string $apiAlias = null;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return TransferTransfer
     */
    public function setName(string $name): TransferTransfer
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return ArrayObject<array-key, PropertyTransfer>
     */
    public function getProperties(): ArrayObject
    {
        if (!isset($this->properties)) $this->properties = new ArrayObject();
        return $this->properties;
    }

    /**
     * @param ArrayObject<array-key, PropertyTransfer> $properties
     * @return TransferTransfer
     */
    public function setProperties(ArrayObject $properties): TransferTransfer
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @param PropertyTransfer $property
     * @return $this
     */
    public function addProperty(PropertyTransfer $property): TransferTransfer
    {
        if (!isset($this->properties)) $this->properties = new ArrayObject();
        $this->properties->append($property);
        return $this;
    }

    /**
     * @return bool
     */
    public function isApi(): bool
    {
        return $this->isApi;
    }

    /**
     * @param bool $isApi
     * @return TransferTransfer
     */
    public function setIsApi(bool $isApi): TransferTransfer
    {
        $this->isApi = $isApi;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getApiAlias(): ?string
    {
        return $this->apiAlias;
    }

    /**
     * @param string|null $apiAlias
     * @return TransferTransfer
     */
    public function setApiAlias(?string $apiAlias): TransferTransfer
    {
        $this->apiAlias = $apiAlias;
        return $this;
    }
}