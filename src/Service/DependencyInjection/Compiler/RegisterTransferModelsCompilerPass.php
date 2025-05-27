<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterTransferModelsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('nelmio_api_doc.model_describers.model_registry')) {
            return;
        }

        if (!$container->hasParameter('transfer.output_dir') || !$container->hasParameter('transfer.namespace')) {
            return;
        }

        $outputDir = $container->getParameter('transfer.output_dir');
        $namespace = $container->getParameter('transfer.namespace');

        foreach (glob($outputDir . '/*Transfer.php') as $filePath) {
            $className = $this->classFromPath($filePath, $outputDir, $namespace);

            if (!class_exists($className)) {
                continue;
            }

            $alias = (new \ReflectionClass($className))->getShortName();
            $alias = preg_replace('/Transfer$/', '', $alias);

            $container->getDefinition('nelmio_api_doc.model_describers.model_registry')
                ->addMethodCall('register', [$alias, $className]);
        }
    }

    private function classFromPath(string $filePath, string $baseDir, string $namespace): string
    {
        $relativePath = str_replace([$baseDir . '/', '.php'], ['', ''], $filePath);
        return $namespace . '\\' . str_replace('/', '\\', $relativePath);
    }
}