<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle;

use PhilippHermes\TransferBundle\Command\TransferGenerateCommand;
use PhilippHermes\TransferBundle\Service\TransferGenerator;
use PhilippHermes\TransferBundle\Service\XmlSchemaParser;
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
                    ->defaultValue(['%kernel.project_dir%/transfers'])
                ->end()
                ->scalarNode('output_dir')
                    ->defaultValue(['%kernel.project_dir%/src/Generated/Transfers'])
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

        $builder
            ->register(XmlSchemaParser::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->setArgument('$schemaDir', '%transfer.schema_dir%');

        $builder
            ->register(TransferGenerator::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->setArgument('$namespace', '%transfer.namespace%')
            ->setArgument('$outputDir', '%transfer.output_dir%');

        $builder
            ->register(TransferGenerateCommand::class)
            ->addTag('console.command');
    }
}