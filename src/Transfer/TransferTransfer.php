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

    /**
     * @var ArrayObject<array-key, PropertyTransfer>
     */
    protected ArrayObject $sensitiveProperties;

    protected ?PropertyTransfer $identifierProperty = null;

    protected bool $isApi = false;

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
     * @return ArrayObject<array-key, PropertyTransfer>
     */
    public function getSensitiveProperties(): ArrayObject
    {
        if (!isset($this->sensitiveProperties)) $this->sensitiveProperties = new ArrayObject();
        return $this->sensitiveProperties;
    }

    /**
     * @param ArrayObject<array-key, PropertyTransfer> $sensitiveProperties
     * @return TransferTransfer
     */
    public function setSensitiveProperties(ArrayObject $sensitiveProperties): TransferTransfer
    {
        $this->sensitiveProperties = $sensitiveProperties;
        return $this;
    }

    /**
     * @param PropertyTransfer $property
     * @return $this
     */
    public function addSensitiveProperty(PropertyTransfer $property): TransferTransfer
    {
        if (!isset($this->sensitiveProperties)) $this->sensitiveProperties = new ArrayObject();
        $this->sensitiveProperties->append($property);
        return $this;
    }

    /**
     * @return PropertyTransfer|null
     */
    public function getIdentifierProperty(): ?PropertyTransfer
    {
        return $this->identifierProperty;
    }

    /**
     * @param PropertyTransfer|null $identifierProperty
     * @return TransferTransfer
     */
    public function setIdentifierProperty(?PropertyTransfer $identifierProperty): TransferTransfer
    {
        $this->identifierProperty = $identifierProperty;
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
}