<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle;

use PhilippHermes\TransferBundle\Command\TransferGenerateCommand;
use PhilippHermes\TransferBundle\Service\Model\Generate\ClassGenerator;
use PhilippHermes\TransferBundle\Service\Model\Generate\ClassGeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\Generate\GeneratorHelper;
use PhilippHermes\TransferBundle\Service\Model\Generate\GeneratorHelperInterface;
use PhilippHermes\TransferBundle\Service\Model\Generate\GetterGenerator;
use PhilippHermes\TransferBundle\Service\Model\Generate\GetterGeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\Generate\PropertyGenerator;
use PhilippHermes\TransferBundle\Service\Model\Generate\PropertyGeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\Generate\SetterGenerator;
use PhilippHermes\TransferBundle\Service\Model\Generate\SetterGeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\Generate\UserGenerator;
use PhilippHermes\TransferBundle\Service\Model\Generate\UserGeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\TransferCleaner;
use PhilippHermes\TransferBundle\Service\Model\TransferCleanerInterface;
use PhilippHermes\TransferBundle\Service\Model\TransferGenerator;
use PhilippHermes\TransferBundle\Service\Model\TransferGeneratorInterface;
use PhilippHermes\TransferBundle\Service\Model\XmlSchemaParser;
use PhilippHermes\TransferBundle\Service\Model\XmlSchemaParserInterface;
use PhilippHermes\TransferBundle\Service\TransferService;
use PhilippHermes\TransferBundle\Service\TransferServiceInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class PhilippHermesTransferBundle extends AbstractBundle
{
    protected string $extensionAlias = 'transfer';

    /**
     * @inheritDoc
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        /** @phpstan-ignore-next-line  */
        $definition->rootNode()
            ->children()
                ->scalarNode('schema_dir')
                    ->defaultValue('%kernel.project_dir%/transfers')
                ->end()
                ->scalarNode('output_dir')
                    ->defaultValue('%kernel.project_dir%/src/Generated/Transfers')
                ->end()
                ->scalarNode('namespace')
                    ->defaultValue('App\\Generated\\Transfers')
                    ->info('The namespace for generated DTOs')
                ->end()
            ->end();
    }

    /**
     *
     * @inheritDoc
     *
     * @param array<array-key, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('transfer.namespace', $config['namespace']);
        $builder->setParameter('transfer.schema_dir', $config['schema_dir']);
        $builder->setParameter('transfer.output_dir', $config['output_dir']);

        $builder->register(GeneratorHelperInterface::class, GeneratorHelper::class);

        $builder
            ->register(ClassGeneratorInterface::class, ClassGenerator::class)
            ->setAutowired(true);

        $builder
            ->register(PropertyGeneratorInterface::class, PropertyGenerator::class)
            ->setAutowired(true);

        $builder
            ->register(GetterGeneratorInterface::class, GetterGenerator::class)
            ->setAutowired(true);

        $builder
            ->register(SetterGeneratorInterface::class, SetterGenerator::class)
            ->setAutowired(true);

        $builder->register(UserGeneratorInterface::class, UserGenerator::class);

        $builder
            ->register(XmlSchemaParserInterface::class, XmlSchemaParser::class)
            ->setArgument('$schemaDir', '%transfer.schema_dir%');

        $builder
            ->register(TransferGeneratorInterface::class, TransferGenerator::class)
            ->setAutowired(true)
            ->setArgument('$namespace', '%transfer.namespace%')
            ->setArgument('$outputDir', '%transfer.output_dir%');

        $builder
            ->register(TransferCleanerInterface::class, TransferCleaner::class)
            ->setArgument('$outputDir', '%transfer.output_dir%');

        $builder
            ->register(TransferServiceInterface::class, TransferService::class)
            ->setAutowired(true)
            ->setAutoconfigured(true);

        $builder
            ->register(TransferGenerateCommand::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->addTag('console.command')
            ->setArgument('$schemaDir', '%transfer.schema_dir%')
            ->setArgument('$outputDir', '%transfer.output_dir%');
    }
}