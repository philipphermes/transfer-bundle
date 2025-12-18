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
     * @inheritDoc
     *
     * @param array<string, mixed> $config
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

    /**
     * @inheritDoc
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        try {
            $outputDir = $builder->getParameter('transfer.output_dir');
        } catch (\Exception) {
            $projectDir = $builder->getParameter('kernel.project_dir');
            if (!is_string($projectDir)) {
                return;
            }
            $outputDir = $projectDir . '/src/Generated/Transfers';
        }

        try {
            $namespace = $builder->getParameter('transfer.namespace');
        } catch (\Exception) {
            $namespace = 'App\\Generated\\Transfers';
        }

        if (!is_string($outputDir) || !is_string($namespace) || !is_dir($outputDir)) {
            return;
        }

        $models = [];
        $filePaths = glob($outputDir . '/*Transfer.php');
        if (!$filePaths) {
            return;
        }

        foreach ($filePaths as $filePath) {
            $className = $this->classFromPath($filePath, $namespace, $outputDir);

            $data = file_get_contents($filePath);
            if (!$data || !str_contains($data, 'use OpenApi\Attributes as OA;')) {
                continue;
            }

            $models[] = [
                'alias' => $this->aliasFromPath($filePath),
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
     * @param string $outputDir
     *
     * @return string
     */
    protected function classFromPath(string $filePath, string $namespace, string $outputDir): string
    {
        $relativePath = str_replace([$outputDir, '/', '.php'], ['', '\\', ''], $filePath);

        return $namespace . $relativePath;
    }

    /**
     * @param string $filePath
     *
     * @return string|null
     */
    protected function aliasFromPath(string $filePath): ?string
    {
        $filename = basename($filePath, '.php');

        return preg_replace('/Transfer$/', '', $filename);
    }
}