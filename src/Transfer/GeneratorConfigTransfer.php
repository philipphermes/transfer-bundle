<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Transfer;

class GeneratorConfigTransfer
{
    protected string $schemaDirectory;

    protected string $outputDirectory;

    protected string $namespace;

    /**
     * @return string
     */
    public function getSchemaDirectory(): string
    {
        return $this->schemaDirectory;
    }

    /**
     * @param string $schemaDirectory
     * @return GeneratorConfigTransfer
     */
    public function setSchemaDirectory(string $schemaDirectory): GeneratorConfigTransfer
    {
        $this->schemaDirectory = $schemaDirectory;
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