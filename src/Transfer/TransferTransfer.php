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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setProperties(ArrayObject $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param PropertyTransfer $property
     *
     * @return $this
     */
    public function addProperty(PropertyTransfer $property): self
    {
        if (!isset($this->properties)) {
            $this->properties = new ArrayObject();
        }

        $this->properties->append($property);

        return $this;
    }
}