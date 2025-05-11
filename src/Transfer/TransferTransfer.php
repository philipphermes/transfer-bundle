<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Transfer;

use ArrayObject;

class TransferTransfer
{
    protected string $name;

    protected string $type;

    /**
     * @var ArrayObject<array-key, PropertyTransfer>
     */
    protected ArrayObject $properties;

    /**
     * @var ArrayObject<array-key, PropertyTransfer>
     */
    protected ArrayObject $sensitiveProperties;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return ArrayObject<array-key, PropertyTransfer>
     */
    public function getProperties(): ArrayObject
    {
        if (!isset($this->properties)) {
            return new ArrayObject();
        }

        return $this->properties;
    }

    /**
     * @param ArrayObject<array-key, PropertyTransfer> $properties
     *
     * @return self
     */
    public function setProperties(ArrayObject $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param PropertyTransfer $property
     *
     * @return self
     */
    public function addProperty(PropertyTransfer $property): self
    {
        if (!isset($this->properties)) {
            $this->properties = new ArrayObject();
        }

        $this->properties->append($property);

        return $this;
    }

    /**
     * @return ArrayObject<array-key, PropertyTransfer>
     */
    public function getSensitiveProperties(): ArrayObject
    {
        if (!isset($this->sensitiveProperties)) {
            return new ArrayObject();
        }

        return $this->sensitiveProperties;
    }

    /**
     * @param ArrayObject<array-key, PropertyTransfer> $properties
     *
     * @return self
     */
    public function setSensitiveProperties(ArrayObject $properties): self
    {
        $this->sensitiveProperties = $properties;

        return $this;
    }

    /**
     * @param PropertyTransfer $property
     *
     * @return self
     */
    public function addSensitiveProperty(PropertyTransfer $property): self
    {
        if (!isset($this->sensitiveProperties)) {
            $this->sensitiveProperties = new ArrayObject();
        }

        $this->sensitiveProperties->append($property);

        return $this;
    }
}