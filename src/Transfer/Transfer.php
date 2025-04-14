<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Transfer;

use ArrayObject;

class Transfer
{
    protected string $name;

    /**
     * @var ArrayObject<array-key, Property>
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
     * @return ArrayObject<array-key, Property>
     */
    public function getProperties(): ArrayObject
    {
        return $this->properties;
    }

    /**
     * @param ArrayObject<array-key, Transfer> $properties
     *
     * @return $this
     */
    public function setProperties(ArrayObject $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param Property $property
     *
     * @return $this
     */
    public function addProperty(Property $property): self
    {
        if (!isset($this->properties)) {
            $this->properties = new ArrayObject();
        }

        $this->properties->append($property);

        return $this;
    }
}