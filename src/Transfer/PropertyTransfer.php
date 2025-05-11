<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Transfer;

class PropertyTransfer
{
    protected string $name;

    protected ?string $singular;

    protected string $type;

    protected ?string $description;

    protected string $realType;

    protected string $annotationType;

    protected bool $isNullable;

    protected bool $isIdentifier;

    protected bool $isSensitive;

    protected bool $isBasic;

    protected bool $isArray;

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
     * @return string|null
     */
    public function getSingular(): ?string
    {
        return $this->singular;
    }

    /**
     * @param string|null $singular
     *
     * @return self
     */
    public function setSingular(?string $singular): self
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
     *
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;

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
     *
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getRealType(): string
    {
        return $this->realType;
    }

    /**
     * @param string $realType
     *
     * @return self
     */
    public function setRealType(string $realType): self
    {
        $this->realType = $realType;

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
     *
     * @return self
     */
    public function setAnnotationType(string $annotationType): self
    {
        $this->annotationType = $annotationType;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * @param bool $isNullable
     *
     * @return self
     */
    public function setIsNullable(bool $isNullable): self
    {
        $this->isNullable = $isNullable;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsIdentifier(): bool
    {
        return $this->isIdentifier;
    }

    /**
     * @param bool $isIdentifier
     *
     * @return self
     */
    public function setIsIdentifier(bool $isIdentifier): self
    {
        $this->isIdentifier = $isIdentifier;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsSensitive(): bool
    {
        return $this->isSensitive;
    }

    /**
     * @param bool $isSensitive
     *
     * @return self
     */
    public function setIsSensitive(bool $isSensitive): self
    {
        $this->isSensitive = $isSensitive;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsBasic(): bool
    {
        return $this->isBasic;
    }

    /**
     * @param bool $isBasic
     *
     * @return self
     */
    public function setIsBasic(bool $isBasic): self
    {
        $this->isBasic = $isBasic;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsArray(): bool
    {
        return $this->isArray;
    }

    /**
     * @param bool $isArray
     *
     * @return self
     */
    public function setIsArray(bool $isArray): self
    {
        $this->isArray = $isArray;

        return $this;
    }
}