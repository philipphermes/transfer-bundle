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
    protected bool $isTransfer = false;
    protected bool $isSingularTransfer = false;
    protected ?string $default = null;
    protected bool $hasDefaultValue = false;
    protected string|int|float|bool|null $defaultValue = null;
    protected ?string $example = null;
    protected bool $isDeprecated = false;

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

    /**
     * @return bool
     */
    public function isTransfer(): bool
    {
        return $this->isTransfer;
    }

    /**
     * @param bool $isTransfer
     * @return PropertyTransfer
     */
    public function setIsTransfer(bool $isTransfer): PropertyTransfer
    {
        $this->isTransfer = $isTransfer;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSingularTransfer(): bool
    {
        return $this->isSingularTransfer;
    }

    /**
     * @param bool $isSingularTransfer
     * @return PropertyTransfer
     */
    public function setIsSingularTransfer(bool $isSingularTransfer): PropertyTransfer
    {
        $this->isSingularTransfer = $isSingularTransfer;
        return $this;
    }

    /**
     * Whether the generated property always holds a value: non-nullable with a default value or `[]`.
     *
     * @return bool
     */
    public function isAlwaysInitialized(): bool
    {
        return !$this->isNullable && ($this->hasDefaultValue || $this->type === 'array');
    }

    /**
     * Name of the generated constant holding the property name: `createdAt` → `CREATED_AT`.
     *
     * @return string
     */
    public function getConstantName(): string
    {
        return strtoupper((string)preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], '$1_$2', $this->name));
    }

    /**
     * Whether the property is declared as a list (`X[]`).
     *
     * @return bool
     */
    public function isCollection(): bool
    {
        return $this->singularType !== null;
    }

    /**
     * The raw `default` attribute from the XML.
     *
     * @return string|null
     */
    public function getDefault(): ?string
    {
        return $this->default;
    }

    /**
     * @param string|null $default
     * @return PropertyTransfer
     */
    public function setDefault(?string $default): PropertyTransfer
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }

    /**
     * The `default` attribute converted to the property type.
     *
     * @return string|int|float|bool|null
     */
    public function getDefaultValue(): string|int|float|bool|null
    {
        return $this->defaultValue;
    }

    /**
     * @param string|int|float|bool $defaultValue
     * @return PropertyTransfer
     */
    public function setDefaultValue(string|int|float|bool $defaultValue): PropertyTransfer
    {
        $this->defaultValue = $defaultValue;
        $this->hasDefaultValue = true;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getExample(): ?string
    {
        return $this->example;
    }

    /**
     * @param string|null $example
     * @return PropertyTransfer
     */
    public function setExample(?string $example): PropertyTransfer
    {
        $this->example = $example;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDeprecated(): bool
    {
        return $this->isDeprecated;
    }

    /**
     * @param bool $isDeprecated
     * @return PropertyTransfer
     */
    public function setIsDeprecated(bool $isDeprecated): PropertyTransfer
    {
        $this->isDeprecated = $isDeprecated;
        return $this;
    }
}
