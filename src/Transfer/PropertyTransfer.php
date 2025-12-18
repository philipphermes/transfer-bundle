<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Transfer;

class PropertyTransfer
{
    protected string $name;
    protected ?string $singular = null;

    protected string $type;
    protected ?string $singularType = null;
    protected string $annotationType;
    protected ?string $singularAnnotationType = null;
    protected ?string $description = null;
    protected bool $isNullable = false;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return PropertyTransfer
     */
    public function setName(string $name): PropertyTransfer
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSingular(): ?string
    {
        return $this->singular;
    }

    /**
     * @param string|null $singular
     * @return PropertyTransfer
     */
    public function setSingular(?string $singular): PropertyTransfer
    {
        $this->singular = $singular;
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
     * @return PropertyTransfer
     */
    public function setType(string $type): PropertyTransfer
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSingularType(): ?string
    {
        return $this->singularType;
    }

    /**
     * @param string|null $singularType
     * @return PropertyTransfer
     */
    public function setSingularType(?string $singularType): PropertyTransfer
    {
        $this->singularType = $singularType;
        return $this;
    }

    /**
     * @return string
     */
    public function getAnnotationType(): string
    {
        return $this->annotationType;
    }

    /**
     * @param string $annotationType
     * @return PropertyTransfer
     */
    public function setAnnotationType(string $annotationType): PropertyTransfer
    {
        $this->annotationType = $annotationType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSingularAnnotationType(): ?string
    {
        return $this->singularAnnotationType;
    }

    /**
     * @param string|null $singularAnnotationType
     * @return PropertyTransfer
     */
    public function setSingularAnnotationType(?string $singularAnnotationType): PropertyTransfer
    {
        $this->singularAnnotationType = $singularAnnotationType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return PropertyTransfer
     */
    public function setDescription(?string $description): PropertyTransfer
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * @param bool $isNullable
     * @return PropertyTransfer
     */
    public function setIsNullable(bool $isNullable): PropertyTransfer
    {
        $this->isNullable = $isNullable;
        return $this;
    }
}