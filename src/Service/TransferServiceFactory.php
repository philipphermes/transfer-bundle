<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service;

use PhilippHermes\TransferBundle\Service\Model\Cleaner\TransferCleaner;
use PhilippHermes\TransferBundle\Service\Model\Cleaner\TransferCleanerInterface;
use PhilippHermes\TransferBundle\Service\Model\Generator\Generator;
use PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps\ClassEndGeneratorStep;
use PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps\ClassGeneratorStep;
use PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps\GeneratorStepInterface;
use PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps\GetterGeneratorStep;
use PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps\PropertyGeneratorStep;
use PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps\SensitiveGeneratorStep;
use PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps\SetterGeneratorStep;
use PhilippHermes\TransferBundle\Service\Model\Generator\Helper\GeneratorHelper;
use PhilippHermes\TransferBundle\Service\Model\Generator\Helper\GeneratorHelperInterface;
use PhilippHermes\TransferBundle\Service\Model\Parser\TransferParser;
use PhilippHermes\TransferBundle\Service\Model\Parser\TransferParserInterface;

class TransferServiceFactory
{
    /**
     * @return GeneratorInterface
     */
    public function createGenerator(): GeneratorInterface
    {
        return new Generator(
            $this->createTransferParser(),
            $this->createTransferCleaner(),
            $this->createGeneratorHelper(),
            $this->createGeneratorSteps(),
        );
    }

    /**
     * @return TransferParserInterface
     */
    protected function createTransferParser(): TransferParserInterface
    {
        return new TransferParser();
    }

    /**
     * @return TransferCleanerInterface
     */
    protected function createTransferCleaner(): TransferCleanerInterface
    {
        return new TransferCleaner();
    }

    /**
     * @return GeneratorHelperInterface
     */
    protected function createGeneratorHelper(): GeneratorHelperInterface
    {
        return new GeneratorHelper();
    }

    /**
     * @return array<GeneratorStepInterface>
     */
    protected function createGeneratorSteps(): array
    {
        return [
            new ClassGeneratorStep(),
            new PropertyGeneratorStep(),
            new GetterGeneratorStep(),
            new SetterGeneratorStep(),
            new SensitiveGeneratorStep(),
            new ClassEndGeneratorStep(),
        ];
    }
}