<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Transfer;

class GeneratorConfigTransfer
{
    protected string $schemaDirectory;

    protected string $outputDirectory;

    protected string $namespace;

    public function getSchemaDirectory(): string
    {
        return $this->schemaDirectory;
    }

    /**
     * @param string $schemaDirectory
     *
     * @return self
     */
    public function setSchemaDirectory(string $schemaDirectory): self
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
     *
     * @return self
     */
    public function setOutputDirectory(string $outputDirectory): self
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
     *
     * @return self
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }
}