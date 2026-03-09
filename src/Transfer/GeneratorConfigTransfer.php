<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Transfer;

class GeneratorConfigTransfer
{
    /**
     * @var array<string>
     */
    protected array $schemaDirectories = [];

    /**
     * @var array<string>
     */
    protected array $excludeDirectories = [];

    protected string $outputDirectory;

    protected string $namespace;

    /**
     * @return array<string>
     */
    public function getSchemaDirectories(): array
    {
        return $this->schemaDirectories;
    }

    /**
     * @param array<string> $schemaDirectories
     * @return GeneratorConfigTransfer
     */
    public function setSchemaDirectories(array $schemaDirectories): GeneratorConfigTransfer
    {
        $this->schemaDirectories = $schemaDirectories;
        return $this;
    }

    /**
     * @param string $schemaDirectory
     * @return GeneratorConfigTransfer
     */
    public function addSchemaDirectory(string $schemaDirectory): GeneratorConfigTransfer
    {
        $this->schemaDirectories[] = $schemaDirectory;
        return $this;
    }

    /**
     * @return array<string>
     */
    public function getExcludeDirectories(): array
    {
        return $this->excludeDirectories;
    }

    /**
     * @param array<string> $excludeDirectories
     * @return GeneratorConfigTransfer
     */
    public function setExcludeDirectories(array $excludeDirectories): GeneratorConfigTransfer
    {
        $this->excludeDirectories = $excludeDirectories;
        return $this;
    }

    /**
     * @param string $excludeDirectory
     * @return GeneratorConfigTransfer
     */
    public function addExcludeDirectory(string $excludeDirectory): GeneratorConfigTransfer
    {
        $this->excludeDirectories[] = $excludeDirectory;
        return $this;
    }

    /**
     * @return string
     */
    public function getOutputDirectory(): string
    {
        return $this->outputDirectory;
    }

    /**
     * @param string $outputDirectory
     * @return GeneratorConfigTransfer
     */
    public function setOutputDirectory(string $outputDirectory): GeneratorConfigTransfer
    {
        $this->outputDirectory = $outputDirectory;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     * @return GeneratorConfigTransfer
     */
    public function setNamespace(string $namespace): GeneratorConfigTransfer
    {
        $this->namespace = $namespace;
        return $this;
    }
}