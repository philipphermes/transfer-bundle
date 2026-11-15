<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Service\Model\Parser;

use DOMDocument;
use InvalidArgumentException;
use PhilippHermes\TransferBundle\Service\Model\Type\PropertyTypeMapper;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;
use SimpleXMLElement;
use Symfony\Component\Finder\Finder;

readonly class TransferParser implements TransferParserInterface
{
    protected const string IDENTIFIER_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    protected const string SCHEMA_FILE = __DIR__ . '/../../../Resources/schema/transfer.xsd';

    public function __construct(
        protected PropertyTypeMapper $propertyTypeMapper,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function parse(GeneratorConfigTransfer $generatorConfigTransfer): TransferCollectionTransfer
    {
        $collection = new TransferCollectionTransfer();

        $allPaths = [];
        foreach ($generatorConfigTransfer->getSchemaDirectories() as $schemaDirectory) {
            $paths = glob($schemaDirectory, GLOB_ONLYDIR);

            if ($paths === false) {
                $collection->addError('Could not parse ' . $schemaDirectory);
                continue;
            }

            if ($paths === []) {
                $collection->addWarning(sprintf("Schema directory '%s' did not match any directory", $schemaDirectory));
                continue;
            }

            $allPaths = array_merge($allPaths, $paths);
        }

        $excludedPaths = [];
        foreach ($generatorConfigTransfer->getExcludeDirectories() as $excludeDirectory) {
            foreach (glob($excludeDirectory, GLOB_ONLYDIR) ?: [] as $excludedPath) {
                $excludedPaths[] = realpath($excludedPath) ?: $excludedPath;
            }
        }

        $allPaths = array_values(array_filter(
            $allPaths,
            fn (string $path): bool => !$this->isExcluded(realpath($path) ?: $path, $excludedPaths),
        ));

        if ($allPaths === []) {
            return $collection;
        }

        $finder = new Finder();
        $finder->files()->in($allPaths)->name('*.xml')->sortByName();

        /**
         * @var array<string, string> $propertyTypesToResolve
         */
        $propertyTypesToResolve = [];
        $parsedFiles = [];

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();

            if (isset($parsedFiles[$absoluteFilePath]) || $this->isExcluded($absoluteFilePath, $excludedPaths)) {
                continue;
            }
            $parsedFiles[$absoluteFilePath] = true;

            $xml = $this->loadXml($absoluteFilePath, $file->getFilename(), $collection);
            if (!$xml) {
                continue;
            }

            foreach ($xml->transfer as $transferElement) {
                if (!isset($transferElement['name'])) {
                    $collection->addError("Missing 'name' attribute in file '{$file->getFilename()}'");

                    continue;
                }

                $transferName = (string)$transferElement['name'];

                if (!$this->isValidIdentifier($transferName)) {
                    $collection->addError(sprintf(
                        "Invalid transfer name '%s' in file '%s': must be a valid PHP identifier",
                        $transferName,
                        $file->getFilename(),
                    ));

                    continue;
                }

                $transfer = $this->getTransferFromCollection($transferName, $collection);

                if (!$transfer) {
                    $transfer = new TransferTransfer();
                    $transfer->setName($transferName);
                }

                $this->mergeApiAttributes($transfer, $transferElement, $file->getFilename(), $collection);

                foreach ($transferElement->property as $propertyElement) {
                    $skip = false;

                    if (!isset($propertyElement['name'])) {
                        $collection->addError(sprintf(
                            "Missing 'name' attribute in transfer '%s' in file '%s'",
                            $transferName,
                            $file->getFilename(),
                        ));

                        $skip = true;
                    }

                    if (!isset($propertyElement['type'])) {
                        $collection->addError(sprintf(
                            "Missing 'type' attribute in transfer '%s' in file '%s'",
                            $transferName,
                            $file->getFilename(),
                        ));

                        $skip = true;
                    }

                    foreach (['name', 'singular'] as $attribute) {
                        if (isset($propertyElement[$attribute]) && !$this->isValidIdentifier((string)$propertyElement[$attribute])) {
                            $collection->addError(sprintf(
                                "Invalid property %s '%s' in transfer '%s' in file '%s': must be a valid PHP identifier",
                                $attribute,
                                $propertyElement[$attribute],
                                $transferName,
                                $file->getFilename(),
                            ));

                            $skip = true;
                        }
                    }

                    if ($skip) {
                        continue;
                    }

                    $property = (new PropertyTransfer())->setName((string)$propertyElement['name']);
                    $propertyTypeToResolveKey = $this->getPropertyTypeToResolveKey($transfer, $property);
                    $type = (string)$propertyElement['type'];

                    if (isset($propertyTypesToResolve[$propertyTypeToResolveKey])) {
                        if ($propertyTypesToResolve[$propertyTypeToResolveKey] !== $type) {
                            $collection->addWarning(sprintf(
                                "Property '%s' of transfer '%s' is already defined with type '%s', ignoring type '%s' from file '%s'",
                                $property->getName(),
                                $transferName,
                                $propertyTypesToResolve[$propertyTypeToResolveKey],
                                $type,
                                $file->getFilename(),
                            ));
                        }

                        continue;
                    }

                    $property
                        ->setDescription(isset($propertyElement['description']) ? (string)$propertyElement['description'] : null)
                        ->setSingular(isset($propertyElement['singular']) ? (string)$propertyElement['singular'] : null)
                        ->setIsNullable($this->parseBool($propertyElement['isNullable']))
                        ->setDefault(isset($propertyElement['default']) ? (string)$propertyElement['default'] : null)
                        ->setExample(isset($propertyElement['example']) ? (string)$propertyElement['example'] : null)
                        ->setIsDeprecated($this->parseBool($propertyElement['deprecated']));

                    $transfer->addProperty($property);
                    $propertyTypesToResolve[$propertyTypeToResolveKey] = $type;
                }

                if (!$this->getTransferFromCollection($transfer->getName(), $collection)) {
                    $collection->addTransfer($transfer);
                }
            }
        }

        $this->propertyTypeMapper->setDefinedTransfers($collection);

        foreach ($collection->getTransfers() as $transfer) {
            foreach ($transfer->getProperties() as $property) {
                try {
                    $this->propertyTypeMapper->addTypes(
                        $generatorConfigTransfer,
                        $property,
                        $propertyTypesToResolve[$this->getPropertyTypeToResolveKey($transfer, $property)],
                    );
                    $this->resolveDefaultValue($property);
                } catch (InvalidArgumentException $exception) {
                    $collection->addError(sprintf(
                        "Transfer '%s', property '%s': %s",
                        $transfer->getName(),
                        $property->getName(),
                        $exception->getMessage(),
                    ));
                }
            }

            if ($this->validateAccessors($transfer, $collection)) {
                $this->validateConstants($transfer, $collection);
            }
        }

        return $collection;
    }

    /**
     * @param string $absoluteFilePath
     * @param string $fileName
     * @param TransferCollectionTransfer $collection
     *
     * @return SimpleXMLElement|null
     */
    protected function loadXml(string $absoluteFilePath, string $fileName, TransferCollectionTransfer $collection): ?SimpleXMLElement
    {
        $previousUseErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $document = new DOMDocument();

            if (!$document->load($absoluteFilePath)) {
                $error = libxml_get_errors()[0] ?? null;
                $collection->addError(sprintf(
                    "Invalid XML in '%s'%s",
                    $fileName,
                    $error ? sprintf(' (line %d): %s', $error->line, trim($error->message)) : '',
                ));

                return null;
            }

            if (!$document->schemaValidate(self::SCHEMA_FILE)) {
                foreach (libxml_get_errors() as $error) {
                    $collection->addWarning(sprintf(
                        "Schema validation failed for '%s' (line %d): %s",
                        $fileName,
                        $error->line,
                        trim($error->message),
                    ));
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseErrors);
        }

        $xml = simplexml_import_dom($document);

        if (!$xml || !isset($xml->transfer)) {
            $collection->addError("Invalid XML structure in '{$fileName}'");

            return null;
        }

        return $xml;
    }

    /**
     * A transfer is an API transfer if any definition says so. The first defined alias wins,
     * a different alias in a later definition is an error.
     *
     * @param TransferTransfer $transfer
     * @param SimpleXMLElement $transferElement
     * @param string $fileName
     * @param TransferCollectionTransfer $collection
     *
     * @return void
     */
    protected function mergeApiAttributes(TransferTransfer $transfer, SimpleXMLElement $transferElement, string $fileName, TransferCollectionTransfer $collection): void
    {
        $transfer->setIsApi($transfer->isApi() || $this->parseBool($transferElement['api']));

        if (!isset($transferElement['apiAlias'])) {
            return;
        }

        $apiAlias = (string)$transferElement['apiAlias'];

        if ($transfer->getApiAlias() === null) {
            $transfer->setApiAlias($apiAlias);

            return;
        }

        if ($transfer->getApiAlias() !== $apiAlias) {
            $collection->addError(sprintf(
                "Conflicting apiAlias for transfer '%s' in file '%s': '%s' is already defined, got '%s'",
                $transfer->getName(),
                $fileName,
                $transfer->getApiAlias(),
                $apiAlias,
            ));
        }
    }

    /**
     * Converts the raw `default` attribute to the property type.
     *
     * @param PropertyTransfer $property
     *
     * @throws InvalidArgumentException if the default is not supported or invalid for the type
     *
     * @return void
     */
    protected function resolveDefaultValue(PropertyTransfer $property): void
    {
        $default = $property->getDefault();

        if ($default === null) {
            return;
        }

        $value = match ($property->isCollection() ? null : $property->getType()) {
            'string' => $default,
            'int' => preg_match('/^-?\d+$/', $default) === 1 ? (int)$default : null,
            'float' => is_numeric($default) ? (float)$default : null,
            'bool' => match (strtolower($default)) {
                'true', '1' => true,
                'false', '0' => false,
                default => null,
            },
            default => throw new InvalidArgumentException('default values are only supported for string, int, float and bool'),
        };

        if ($value === null) {
            throw new InvalidArgumentException(sprintf("invalid default '%s' for type '%s'", $default, $property->getType()));
        }

        $property->setDefaultValue($value);
    }

    /**
     * PHP method names are case-insensitive, so accessors of different properties must not collide.
     *
     * @param TransferTransfer $transfer
     * @param TransferCollectionTransfer $collection
     *
     * @return bool false if a collision was reported
     */
    protected function validateAccessors(TransferTransfer $transfer, TransferCollectionTransfer $collection): bool
    {
        $isValid = true;

        /** @var array<string, string> $methodOwners lowercase method name => property name */
        $methodOwners = [];

        foreach ($transfer->getProperties() as $property) {
            foreach ($this->getAccessorNames($property) as $method) {
                $owner = $methodOwners[strtolower($method)] ?? null;

                if ($owner !== null) {
                    $collection->addError(sprintf(
                        "Transfer '%s': method '%s' of property '%s' collides with property '%s'",
                        $transfer->getName(),
                        $method,
                        $property->getName(),
                        $owner,
                    ));
                    $isValid = false;

                    break;
                }
            }

            foreach ($this->getAccessorNames($property) as $method) {
                $methodOwners[strtolower($method)] ??= $property->getName();
            }
        }

        return $isValid;
    }

    /**
     * Each property gets a constant with its name in UPPER_SNAKE_CASE, which must not collide with another one.
     *
     * @param TransferTransfer $transfer
     * @param TransferCollectionTransfer $collection
     *
     * @return void
     */
    protected function validateConstants(TransferTransfer $transfer, TransferCollectionTransfer $collection): void
    {
        /** @var array<string, string> $constantOwners constant name => owner description */
        $constantOwners = [];

        if ($transfer->isApi()) {
            $constantOwners['API_ALIAS'] = 'the API_ALIAS constant';
        }

        foreach ($transfer->getProperties() as $property) {
            $constant = $property->getConstantName();
            $owner = $constantOwners[$constant] ?? null;

            if ($owner !== null) {
                $collection->addError(sprintf(
                    "Transfer '%s': constant '%s' of property '%s' collides with %s",
                    $transfer->getName(),
                    $constant,
                    $property->getName(),
                    $owner,
                ));

                continue;
            }

            $constantOwners[$constant] = sprintf("property '%s'", $property->getName());
        }
    }

    /**
     * @param PropertyTransfer $property
     *
     * @return array<string>
     */
    protected function getAccessorNames(PropertyTransfer $property): array
    {
        $name = ucfirst($property->getName());
        $methods = ['get' . $name, 'set' . $name, 'has' . $name];

        if ($property->isCollection()) {
            $methods[] = 'add' . ucfirst($property->getSingular() ?? $property->getName());
        }

        return $methods;
    }

    /**
     * @param string $path
     * @param array<string> $excludedPaths
     *
     * @return bool
     */
    protected function isExcluded(string $path, array $excludedPaths): bool
    {
        foreach ($excludedPaths as $excludedPath) {
            if ($path === $excludedPath || str_starts_with($path, rtrim($excludedPath, '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected function isValidIdentifier(string $name): bool
    {
        return preg_match(self::IDENTIFIER_PATTERN, $name) === 1;
    }

    /**
     * @param SimpleXMLElement|null $attribute
     *
     * @return bool
     */
    protected function parseBool(?SimpleXMLElement $attribute): bool
    {
        return $attribute !== null && in_array(strtolower(trim((string)$attribute)), ['true', '1'], true);
    }

    /**
     * @param string $name
     * @param TransferCollectionTransfer $transferCollectionTransfer
     *
     * @return TransferTransfer|null
     */
    protected function getTransferFromCollection(string $name, TransferCollectionTransfer $transferCollectionTransfer): ?TransferTransfer
    {
        foreach ($transferCollectionTransfer->getTransfers() as $transferTransfer) {
            if ($transferTransfer->getName() === $name) {
                return $transferTransfer;
            }
        }

        return null;
    }

    /**
     * @param TransferTransfer $transferTransfer
     * @param PropertyTransfer $propertyTransfer
     *
     * @return string
     */
    protected function getPropertyTypeToResolveKey(TransferTransfer $transferTransfer, PropertyTransfer $propertyTransfer): string
    {
        return sprintf(
            '%s_%s',
            $transferTransfer->getName(),
            $propertyTransfer->getName(),
        );
    }
}
