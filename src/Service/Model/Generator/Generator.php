<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps\PropertyGeneratorStepInterface;
use PhilippHermes\TransferBundle\Service\Model\Type\PropertyTypeMapper;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\PropertyTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class Generator implements GeneratorInterface
{
    /**
     * @param array<PropertyGeneratorStepInterface> $propertyGeneratorSteps
     */
    public function __construct(
        protected readonly array $propertyGeneratorSteps,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function generate(
        GeneratorConfigTransfer $generatorConfigTransfer,
        TransferCollectionTransfer $transferCollectionTransfer,
    ): void {
        foreach ($transferCollectionTransfer->getTransfers() as $transfer) {
            $transfer = $this->addRoleProperty($transfer);
            $this->generateTransfer($generatorConfigTransfer, $transfer);
        }
    }

    /**
     * @param GeneratorConfigTransfer $generatorConfig
     * @param TransferTransfer $transfer
     * @return void
     */
    protected function generateTransfer(GeneratorConfigTransfer $generatorConfig, TransferTransfer $transfer)
    {
        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addComment('This file is auto-generated.');

        $namespace = $file->addNamespace($generatorConfig->getNamespace());
        $this->generateUses($transfer, $namespace);

        $class = $namespace->addClass($transfer->getName() . 'Transfer');
        $this->generateInheritance($transfer, $class);

        foreach ($transfer->getProperties() as $property) {
            foreach ($this->propertyGeneratorSteps as $propertyGeneratorStep) {
                $propertyGeneratorStep->generate($property, $class);
            }

            if ($property->isIdentifier()) {
                $transfer->setIdentifierProperty($property);
            }

            if ($property->isSensitive()) {
                $transfer->addSensitiveProperty($property);
            }
        }

        $this->generateUserProperties($transfer, $class);

        if (!is_dir($generatorConfig->getOutputDirectory())) {
            mkdir($generatorConfig->getOutputDirectory(), 0777, true);
        }

        file_put_contents($generatorConfig->getOutputDirectory() . '/' . $transfer->getName() . 'Transfer.php', (string)$file);
    }

    /**
     * @param TransferTransfer $transfer
     * @param PhpNamespace $namespace
     * @return void
     */
    protected function generateUses(TransferTransfer $transfer, PhpNamespace $namespace): void
    {
        foreach ($transfer->getProperties() as $propertyTransfer) {
            if (!in_array($propertyTransfer->getType(), PropertyTypeMapper::PHP_TYPES, true) && !str_contains($propertyTransfer->getType(), 'Transfer')) {
                $namespace->addUse($propertyTransfer->getType());
            }

            if ($propertyTransfer->getSingularType() && !in_array($propertyTransfer->getSingularType(), PropertyTypeMapper::PHP_TYPES, true) && !str_contains($propertyTransfer->getSingularType(), 'Transfer')) {
                $namespace->addUse($propertyTransfer->getSingularType());
            }
        }
    }

    /**
     * @param TransferTransfer $transfer
     * @param ClassType $class
     * @return void
     */
    protected function generateInheritance(TransferTransfer $transfer, ClassType $class): void
    {
        if ($transfer->getType() !== 'user') {
            return;
        }

        $class->addImplement('Symfony\Component\Security\Core\User\UserInterface');

        foreach ($transfer->getProperties() as $property) {
            if ($property->getName() === 'password') {
                $class->addImplement('Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface');
                break;
            }
        }
    }

    /**
     * @param TransferTransfer $transfer
     * @param ClassType $class
     * @return void
     */
    protected function generateUserProperties(
        TransferTransfer $transfer,
        ClassType $class,
    ): void {
        if ($transfer->getType() !== 'user' || !$transfer->getIdentifierProperty()?->getName()) { //TODO validate
            return;
        }

        $methodUserIdentifier = $class->addMethod('getUserIdentifier');
        $methodUserIdentifier->setPublic();
        $methodUserIdentifier->setReturnType('string');
        $methodUserIdentifier->setComment('@return string');
        $methodUserIdentifier->addBody('return $this->' . $transfer->getIdentifierProperty()->getName() . ';');

        $methodEraseCredentials = $class->addMethod('eraseCredentials');
        $methodEraseCredentials->setPublic();
        $methodEraseCredentials->setReturnType('void');
        $methodEraseCredentials->setComment('@return void');

        foreach ($transfer->getSensitiveProperties() as $property) {
            $methodEraseCredentials->addBody('$this->' . $property->getName() . ' = null;');
        }
    }

    /**
     * @param TransferTransfer $transfer
     * @return TransferTransfer
     */
    protected function addRoleProperty(TransferTransfer $transfer): TransferTransfer
    {
        if ($transfer->getType() !== 'user') {
            return $transfer;
        }

        foreach ($transfer->getProperties() as $propertyTransfer) {
            if ($propertyTransfer->getName() === 'roles') {
                return $transfer;
            }
        }

        return $transfer->addProperty((new PropertyTransfer())
            ->setName('roles')
            ->setSingular('role')
            ->setType('array')
            ->setSingularType('string')
            ->setAnnotationType('array<array-key, string>')
            ->setSingularAnnotationType('string'),
        );
    }
}