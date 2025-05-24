<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service;

use PhilippHermes\TransferBundle\Service\Model\Cleaner\TransferCleaner;
use PhilippHermes\TransferBundle\Service\Model\Cleaner\TransferCleanerInterface;
use PhilippHermes\TransferBundle\Service\Model\Generator\Generator;
use PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps\AdderPropertyGeneratorStep;
use PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps\GetterPropertyGeneratorStep;
use PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps\PropertyGeneratorStepInterface;
use PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps\PropertyPropertyGeneratorStep;
use PhilippHermes\TransferBundle\Service\Model\Generator\PropertyGeneratorSteps\SetterPropertyGeneratorStep;
use PhilippHermes\TransferBundle\Service\Model\Parser\TransferParser;
use PhilippHermes\TransferBundle\Service\Model\Parser\TransferParserInterface;
use PhilippHermes\TransferBundle\Service\Model\Type\PropertyTypeMapper;

class TransferServiceFactory
{
    /**
     * @return GeneratorInterface
     */
    public function createGenerator(): GeneratorInterface
    {
        //TODO cleaner

        return new Generator(
            $this->createPropertyGeneratorSteps(),
        );
    }

    /**
     * @return TransferParserInterface
     */
    public function createTransferParser(): TransferParserInterface
    {
        return new TransferParser(new PropertyTypeMapper());
    }

    /**
     * @return TransferCleanerInterface
     */
    public function createTransferCleaner(): TransferCleanerInterface
    {
        return new TransferCleaner();
    }

    /**
     * @return array<PropertyGeneratorStepInterface>
     */
    protected function createPropertyGeneratorSteps(): array
    {
        return [
            new PropertyPropertyGeneratorStep(),
            new GetterPropertyGeneratorStep(),
            new SetterPropertyGeneratorStep(),
            new AdderPropertyGeneratorStep(),
        ];
    }
}