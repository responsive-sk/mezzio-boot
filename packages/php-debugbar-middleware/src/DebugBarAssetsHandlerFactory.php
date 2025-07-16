<?php

declare(strict_types=1);

namespace ResponsiveSk\PhpDebugBarMiddleware;

use Psr\Container\ContainerInterface;

/**
 * Factory for DebugBarAssetsHandler
 */
class DebugBarAssetsHandlerFactory
{
    public function __invoke(ContainerInterface $container): DebugBarAssetsHandler
    {
        $config = $this->getConfig($container);
        $debugBarConfig = $config['debugbar'] ?? [];
        
        // Get custom resources path if configured
        $resourcesPath = $debugBarConfig['resources_path'] ?? null;
        
        return new DebugBarAssetsHandler($resourcesPath);
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfig(ContainerInterface $container): array
    {
        try {
            $config = $container->get('config');
            return is_array($config) ? $config : [];
        } catch (\Exception) {
            return [];
        }
    }
}
