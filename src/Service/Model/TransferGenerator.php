<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Service\Model;

use PhilippHermes\TransferBundle\Service\Model\Generate\ClassGeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\Generate\GetterGeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\Generate\PropertyGeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\Generate\SetterGeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\Generate\UserGeneratorInterface;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

readonly class TransferGenerator implements TransferGeneratorInterface
{
    /**
     * @param string $namespace
     * @param string $outputDir
     * @param ClassGeneratorInterface $classGenerator
     * @param PropertyGeneratorInterface $propertyGenerator
     * @param GetterGeneratorInterface $getterGenerator
     * @param SetterGeneratorInterface $setterGenerator
     * @param UserGeneratorInterface $userGenerator
     */
    public function __construct(
        protected string $namespace,
        protected string $outputDir,
        protected ClassGeneratorInterface $classGenerator,
        protected PropertyGeneratorInterface $propertyGenerator,
        protected GetterGeneratorInterface $getterGenerator,
        protected SetterGeneratorInterface $setterGenerator,
        protected UserGeneratorInterface $userGenerator,
    ) {}

    /**
     * @inheritDoc
     */
    public function generateTransfer(TransferTransfer $transfer): void
    {
        if ($transfer->getType() === 'user') {
            $transfer = $this->addRolesProperty($transfer);
        }

        $code = $this->classGenerator->generateClassHeader($transfer, $this->namespace);
        $code = $this->propertyGenerator->generateProperties($transfer, $code);
        $code = $this->generateGettersSettersAndAdders($transfer, $code);

        $code .= "}\n";

        $filePath = $this->outputDir . "/" . $transfer->getName() . "Transfer.php";
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, $code);
    }

    /**
     * @param TransferTransfer $transferTransfer
     * @param string $code
     *
     * @return string
     */
    protected function generateGettersSettersAndAdders(TransferTransfer $transferTransfer, string $code): string
    {
        $sensitiveProperties = [];
        $createdIdentifierGetter = false;

        foreach ($transferTransfer->getProperties() as $property) {
            $code = $this->getterGenerator->generateGetter($property, $code);
            $code = $this->setterGenerator->generateSetter($property, $code);
            $code = $this->setterGenerator->generateAdder($property, $code);

            if ($property->getIsIdentifier() && !$createdIdentifierGetter) {
                $code = $this->getterGenerator->generateGetter($property, $code, 'userIdentifier');

                $createdIdentifierGetter = true;
            }

            if ($property->getIsSensitive()) {
                $sensitiveProperties[] = $property;
            }
        }

        if ($transferTransfer->getType() === 'user') {
            return $this->userGenerator->generateEraseCredentials($sensitiveProperties, $code);
        }

        return $code;
    }

    /**
     * @param TransferTransfer $transferTransfer
     *
     * @return TransferTransfer
     */
    protected function addRolesProperty(TransferTransfer $transferTransfer): TransferTransfer
    {
        $hasRoleProperty = false;

        foreach ($transferTransfer->getProperties() as $property) {
            if ($property->getName() === 'roles') {
                $hasRoleProperty = true;
            }
        }

        if ($hasRoleProperty) {
            return $transferTransfer;
        }

        $transferTransfer->addProperty((new PropertyTransfer())
            ->setName('roles')
            ->setSingular('role')
            ->setType('string[]')
            ->setIsNullable(false)
            ->setDescription(null)
            ->setIsIdentifier(false)
            ->setIsSensitive(false),
        );

        return $transferTransfer;
    }
}
