<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Transfer;

use ArrayObject;
use DateTimeImmutable;
use DateTime;
use DateTimeInterface;
use ReflectionClass;
use ReflectionProperty;

abstract class AbstractTransfer
{
    /**
     * @param string $keyFormat "snake_case" or "camelCase"
     * @param bool $recursive Whether to recursively convert nested objects
     * @param string $dateTimeFormat Format for DateTime conversion (e.g., DateTimeInterface::ATOM, 'Y-m-d H:i:s')
     *
     * @return array<string, mixed>
     */
    public function toArray(string $keyFormat = 'camelCase', bool $recursive = true, string $dateTimeFormat = DateTimeInterface::ATOM): array
    {
        $result = [];
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED) as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();
            
            if (!$property->isInitialized($this)) {
                continue;
            }
            
            $value = $property->getValue($this);

            if (!isset($value)) {
                continue;
            }

            $key = $keyFormat === 'snake_case' ? $this->toSnakeCase($propertyName) : $propertyName;

            if ($value instanceof ArrayObject) {
                $result[$key] = [];
                foreach ($value as $item) {
                    $result[$key][] = $this->convertValueToArray($item, $keyFormat, $recursive, $dateTimeFormat);
                }
            } elseif (is_array($value)) {
                $result[$key] = [];
                foreach ($value as $item) {
                    $result[$key][] = $this->convertValueToArray($item, $keyFormat, $recursive, $dateTimeFormat);
                }
            } else {
                $result[$key] = $this->convertValueToArray($value, $keyFormat, $recursive, $dateTimeFormat);
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     * @param string $keyFormat "snake_case" or "camelCase"
     * @param bool $recursive Whether to recursively convert nested arrays to objects
     * @param string $dateTimeFormat Format for DateTime parsing (e.g., DateTimeInterface::ATOM, 'Y-m-d H:i:s')
     *
     * @return static
     */
    public function fromArray(array $data, string $keyFormat = 'camelCase', bool $recursive = true, string $dateTimeFormat = DateTimeInterface::ATOM): static
    {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED) as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();
            $key = $keyFormat === 'snake_case' ? $this->toSnakeCase($propertyName) : $propertyName;

            if (!isset($data[$key])) {
                continue;
            }

            $value = $data[$key];
            $propertyType = $property->getType();

            if (!$propertyType) {
                $property->setValue($this, $value);
                continue;
            }

            $typeName = $propertyType instanceof \ReflectionNamedType ? $propertyType->getName() : null;

            if (!$typeName) {
                $property->setValue($this, $value);
                continue;
            }

            if ($typeName === 'ArrayObject') {
                if (is_array($value)) {
                    $arrayObject = new ArrayObject();
                    $itemType = $this->extractItemTypeFromDocComment($property);
                    foreach ($value as $item) {
                        if ($recursive && is_array($item) && $itemType && class_exists($itemType) && method_exists($itemType, 'fromArray')) {
                            $arrayObject->append((new $itemType())->fromArray($item, $keyFormat, $recursive, $dateTimeFormat));
                        } else {
                            $arrayObject->append($item);
                        }
                    }
                    $property->setValue($this, $arrayObject);
                } else {
                    $property->setValue($this, new ArrayObject($value));
                }
            } elseif ($typeName === 'array') {
                if (is_array($value)) {
                    $array = [];
                    $itemType = $this->extractItemTypeFromDocComment($property);
                    foreach ($value as $item) {
                        if ($recursive && is_array($item) && $itemType && class_exists($itemType) && method_exists($itemType, 'fromArray')) {
                            $array[] = (new $itemType())->fromArray($item, $keyFormat, $recursive, $dateTimeFormat);
                        } else {
                            $array[] = $item;
                        }
                    }
                    $property->setValue($this, $array);
                } else {
                    $property->setValue($this, $value);
                }
            } elseif ($typeName === DateTime::class || $typeName === DateTimeImmutable::class) {
                if (is_string($value)) {
                    $dateTime = $typeName === DateTimeImmutable::class 
                        ? DateTimeImmutable::createFromFormat($dateTimeFormat, $value)
                        : DateTime::createFromFormat($dateTimeFormat, $value);
                    $property->setValue($this, $dateTime ?: new $typeName($value));
                } else {
                    $property->setValue($this, $value);
                }
            } elseif ($recursive && is_array($value) && class_exists($typeName) && method_exists($typeName, 'fromArray')) {
                $property->setValue($this, (new $typeName())->fromArray($value, $keyFormat, $recursive, $dateTimeFormat));
            } else {
                $property->setValue($this, $value);
            }
        }

        return $this;
    }

    /**
     * @param mixed $value
     * @param string $keyFormat
     * @param bool $recursive
     * @param string $dateTimeFormat
     *
     * @return mixed
     */
    private function convertValueToArray(mixed $value, string $keyFormat, bool $recursive, string $dateTimeFormat): mixed
    {
        if ($recursive && is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray($keyFormat, $recursive, $dateTimeFormat);
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($dateTimeFormat);
        }

        if ($recursive && is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        if ($value instanceof ArrayObject) {
            return $value->getArrayCopy();
        }

        return $value;
    }

    /**
     * @param ReflectionProperty $property
     *
     * @return string|null
     */
    private function extractItemTypeFromDocComment(ReflectionProperty $property): ?string
    {
        $docComment = $property->getDocComment();
        if (!$docComment) {
            return null;
        }

        if (preg_match('/@var\s+(?:ArrayObject|array)<[^,]+,\s*([^>|\s]+)/', $docComment, $matches)) {
            $className = trim($matches[1]);
            
            if (str_contains($className, '\\')) {
                return $className;
            }
            
            $declaringClass = $property->getDeclaringClass();
            $namespace = $declaringClass->getNamespaceName();
            
            if ($namespace) {
                $fullyQualifiedClassName = $namespace . '\\' . $className;
                if (class_exists($fullyQualifiedClassName)) {
                    return $fullyQualifiedClassName;
                }
            }
            
            if (class_exists($className)) {
                return $className;
            }
            
            return $className;
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
