<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle;

use PhilippHermes\TransferBundle\Command\TransferGenerateCommand;
use PhilippHermes\TransferBundle\Service\TransferService;
use PhilippHermes\TransferBundle\Service\TransferServiceFactory;
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

        $builder->register(TransferServiceFactory::class, TransferServiceFactory::class);

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
            ->setArgument('$outputDir', '%transfer.output_dir%')
            ->setArgument('$namespace', '%transfer.namespace%');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $outputDir = $builder->getParameter('transfer.output_dir');
        $namespace = $builder->getParameter('transfer.namespace');

        if (!is_dir($outputDir)) {
            return;
        }

        $models = [];

        foreach (glob($outputDir . '/*Transfer.php') as $filePath) {
            $className = $this->classFromPath($filePath, $namespace, $outputDir);

            if (!class_exists($className)) {
                require_once $filePath;
            }

            $alias = (new \ReflectionClass($className))->getShortName();
            $alias = preg_replace('/Transfer$/', '', $alias);

            $models[] = [
                'alias' => $alias,
                'type' => $className,
            ];
        }

        if ($models !== []) {
            $builder->prependExtensionConfig('nelmio_api_doc', [
                'models' => [
                    'names' => $models,
                ],
            ]);
        }
    }

    /**
     * @param string $filePath
     * @param string $namespace
     * @param string $basePath
     *
     * @return string
     */
    private function classFromPath(string $filePath, string $namespace, string $basePath): string
    {
        $relativePath = str_replace([$basePath, '/', '.php'], ['', '\\', ''], $filePath);
        return $namespace . '\\' . $relativePath;
    }

}