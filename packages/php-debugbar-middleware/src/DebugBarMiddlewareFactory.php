<?php

declare(strict_types=1);

namespace ResponsiveSk\PhpDebugBarMiddleware;

use DebugBar\StandardDebugBar;
use Psr\Container\ContainerInterface;

/**
 * Factory for DebugBarMiddleware
 */
class DebugBarMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): DebugBarMiddleware
    {
        $config = $this->getConfig($container);
        $debugBarConfig = $config['debugbar'] ?? [];
        
        // Create DebugBar instance
        $debugBar = new StandardDebugBar();
        
        // Add custom collectors if configured
        if (isset($debugBarConfig['collectors'])) {
            $this->configureCollectors($debugBar, $debugBarConfig['collectors'], $container);
        }
        
        // Get asset path
        $assetPath = $debugBarConfig['asset_path'] ?? '/debugbar';
        
        return new DebugBarMiddleware($debugBar, $assetPath);
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

    /**
     * @param array<string, mixed> $collectorsConfig
     */
    private function configureCollectors(
        StandardDebugBar $debugBar, 
        array $collectorsConfig, 
        ContainerInterface $container
    ): void {
        // Add configuration collector if enabled
        if ($collectorsConfig['config'] ?? false) {
            $config = $this->getConfig($container);
            $debugBar->addCollector(new \DebugBar\DataCollector\ConfigCollector($config));
        }
        
        // Additional collectors can be added here based on configuration
        // For example: PDO collector, Monolog collector, etc.
    }
}
