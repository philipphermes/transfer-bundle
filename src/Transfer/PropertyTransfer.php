<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Transfer;

class PropertyTransfer
{
    protected string $name;

    protected ?string $singular;

    protected string $type;

    protected ?string $description;

    protected bool $isNullable;

    protected bool $isIdentifier;

    protected bool $isSensitive;

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
     * @return string|null
     */
    public function getSingular(): ?string
    {
        return $this->singular;
    }

    /**
     * @param string|null $singular
     *
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setIsSensitive(bool $isSensitive): self
    {
        $this->isSensitive = $isSensitive;

        return $this;
    }
}