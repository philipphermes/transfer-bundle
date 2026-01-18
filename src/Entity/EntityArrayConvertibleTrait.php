<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Entity;

use ArrayObject;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\Proxy;
use ReflectionClass;
use ReflectionProperty;

trait EntityArrayConvertibleTrait
{
    private array $visitedEntities = [];

    /**
     * @param string $keyFormat "snake_case" or "camelCase"
     * @param bool $recursive Whether to recursively convert nested objects
     * @param string $dateTimeFormat Format for DateTime conversion
     * @param int $maxDepth Maximum depth for recursive conversion (prevents infinite loops)
     *
     * @return array<string, mixed>
     */
    public function toArray(
        string $keyFormat = 'camelCase',
        bool $recursive = true,
        string $dateTimeFormat = DateTimeInterface::ATOM,
        int $maxDepth = 10
    ): array {
        $this->visitedEntities = [];
        return $this->entityToArray($this, $keyFormat, $recursive, $dateTimeFormat, 0, $maxDepth);
    }

    /**
     * @param array<string, mixed> $data
     * @param string $keyFormat "snake_case" or "camelCase"
     * @param bool $recursive Whether to recursively convert nested arrays
     * @param string $dateTimeFormat Format for DateTime parsing
     *
     * @return static
     */
    public function fromArray(
        array $data,
        string $keyFormat = 'camelCase',
        bool $recursive = true,
        string $dateTimeFormat = DateTimeInterface::ATOM
    ): static {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC) as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();
            
            if (str_starts_with($propertyName, '__')) {
                continue;
            }
            
            $key = $keyFormat === 'snake_case' ? $this->toSnakeCase($propertyName) : $propertyName;

            if (!isset($data[$key])) {
                continue;
            }

            $value = $data[$key];
            $propertyType = $property->getType();

            if (!$propertyType) {
                continue;
            }

            $typeName = $propertyType instanceof \ReflectionNamedType ? $propertyType->getName() : null;

            if (!$typeName) {
                continue;
            }

            if ($typeName === DateTime::class || $typeName === DateTimeImmutable::class) {
                if (is_string($value)) {
                    $dateTime = $typeName === DateTimeImmutable::class
                        ? DateTimeImmutable::createFromFormat($dateTimeFormat, $value)
                        : DateTime::createFromFormat($dateTimeFormat, $value);
                    $property->setValue($this, $dateTime ?: new $typeName($value));
                }
            } elseif ($typeName === 'array' && is_array($value)) {
                $property->setValue($this, $value);
            } elseif (enum_exists($typeName)) {
                if (is_string($value) || is_int($value)) {
                    $enumValue = $typeName::tryFrom($value);
                    if ($enumValue !== null) {
                        $property->setValue($this, $enumValue);
                    }
                }
            } elseif (!in_array($typeName, ['int', 'float', 'string', 'bool', 'array', 'object', 'mixed'], true)) {
                if (is_scalar($value)) {
                    $property->setValue($this, $value);
                }
            } else {
                $property->setValue($this, $value);
            }
        }

        return $this;
    }

    /**
     * @param object $entity
     * @param string $keyFormat
     * @param bool $recursive
     * @param string $dateTimeFormat
     * @param int $currentDepth
     * @param int $maxDepth
     *
     * @return array<string, mixed>
     */
    private function entityToArray(
        object $entity,
        string $keyFormat,
        bool $recursive,
        string $dateTimeFormat,
        int $currentDepth,
        int $maxDepth
    ): array {
        if ($currentDepth >= $maxDepth) {
            return [];
        }

        $objectHash = spl_object_hash($entity);
        if (isset($this->visitedEntities[$objectHash])) {
            return ['_circular_reference' => true];
        }

        $this->visitedEntities[$objectHash] = true;

        $result = [];
        $reflection = new ReflectionClass($entity);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC) as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();

            if (str_starts_with($propertyName, '__')) {
                continue;
            }

            if (!$property->isInitialized($entity)) {
                continue;
            }

            $value = $property->getValue($entity);

            if (!isset($value)) {
                continue;
            }

            if ($value instanceof Proxy && !$value->__isInitialized()) {
                continue;
            }

            $key = $keyFormat === 'snake_case' ? $this->toSnakeCase($propertyName) : $propertyName;

            $result[$key] = $this->convertValueToArray(
                $value,
                $keyFormat,
                $recursive,
                $dateTimeFormat,
                $currentDepth + 1,
                $maxDepth
            );
        }

        unset($this->visitedEntities[$objectHash]);

        return $result;
    }

    /**
     * @param mixed $value
     * @param string $keyFormat
     * @param bool $recursive
     * @param string $dateTimeFormat
     * @param int $currentDepth
     * @param int $maxDepth
     *
     * @return mixed
     */
    private function convertValueToArray(
        mixed $value,
        string $keyFormat,
        bool $recursive,
        string $dateTimeFormat,
        int $currentDepth,
        int $maxDepth
    ): mixed {
        if ($value instanceof Proxy && !$value->__isInitialized()) {
            return null;
        }

        if ($value instanceof Collection) {
            if (!$recursive || $currentDepth >= $maxDepth) {
                return [];
            }

            $result = [];
            foreach ($value as $item) {
                if (is_object($item)) {
                    $result[] = $this->entityToArray($item, $keyFormat, $recursive, $dateTimeFormat, $currentDepth, $maxDepth);
                } else {
                    $result[] = $item;
                }
            }
            return $result;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($dateTimeFormat);
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof ArrayObject || is_array($value)) {
            if (!$recursive || $currentDepth >= $maxDepth) {
                return [];
            }

            $result = [];
            foreach ($value as $item) {
                if (is_object($item)) {
                    $result[] = $this->entityToArray($item, $keyFormat, $recursive, $dateTimeFormat, $currentDepth, $maxDepth);
                } else {
                    $result[] = $item;
                }
            }
            return $result;
        }

        if ($recursive && is_object($value) && $currentDepth < $maxDepth) {
            return $this->entityToArray($value, $keyFormat, $recursive, $dateTimeFormat, $currentDepth, $maxDepth);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        if (is_scalar($value)) {
            return $value;
        }

        return null;
    }

    /**
     * @param string $input
     *
     * @return string
     */
    private function toSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input) ?? $input);
    }
}
